<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Invoicing\Models\Invoice;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectRevenue;
use Modules\Project\Models\ProjectCost;

/**
 * Service for syncing invoices to project revenues.
 *
 * This service provides synchronization between:
 * - Invoices (Invoicing module)
 * - Project revenues (Project module)
 *
 * When an invoice has line items allocated to projects or is linked
 * to projects via the invoice_project pivot, invoice amounts are
 * synced as project revenues for accurate project financial tracking.
 */
class InvoiceProjectSyncService
{
    /**
     * Sync an invoice to all its allocated projects.
     *
     * Creates/updates project revenue entries based on invoice-project allocations.
     */
    public function syncInvoiceToProjects(Invoice $invoice): array
    {
        $invoice->load(['projects', 'items']);

        // Get projects either from pivot table or from line items
        $projectAllocations = $this->getProjectAllocations($invoice);

        if (empty($projectAllocations)) {
            return [
                'success' => false,
                'message' => 'Invoice has no project allocations',
                'synced' => 0,
            ];
        }

        $synced = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($projectAllocations as $projectId => $allocation) {
                $project = Project::find($projectId);
                if (!$project) {
                    $errors[] = "Project ID {$projectId} not found";
                    continue;
                }

                $result = $this->syncInvoiceToProject($invoice, $project, $allocation['amount']);
                if ($result['success']) {
                    $synced++;
                } else {
                    $errors[] = $result['message'];
                }
            }

            DB::commit();

            return [
                'success' => count($errors) === 0,
                'message' => "Synced to {$synced} project(s)" . (count($errors) > 0 ? ", " . count($errors) . " error(s)" : ''),
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice project sync failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'synced' => 0,
            ];
        }
    }

    /**
     * Get project allocations from invoice.
     *
     * Priority:
     * 1. invoice_project pivot table (explicit allocations)
     * 2. Line items with project_id (calculated from item totals)
     * 3. Invoice-level project_id (single project allocation)
     *
     * All amounts are converted to base currency (EGP) for proper financial tracking.
     *
     * @return array<int, array{amount: float, source: string}>
     */
    protected function getProjectAllocations(Invoice $invoice): array
    {
        $allocations = [];
        $exchangeRate = $this->getInvoiceExchangeRate($invoice);

        // First, check invoice_project pivot table
        if ($invoice->projects->isNotEmpty()) {
            foreach ($invoice->projects as $project) {
                $amount = $project->pivot->allocated_amount ?? 0;
                // Convert to base currency
                $allocations[$project->id] = [
                    'amount' => $this->convertToBaseCurrency($amount, $invoice),
                    'source' => 'pivot',
                ];
            }
            return $allocations;
        }

        // Second, check line items with project_id
        $itemAllocations = $invoice->items()
            ->whereNotNull('project_id')
            ->selectRaw('project_id, SUM(total) as total_amount')
            ->groupBy('project_id')
            ->pluck('total_amount', 'project_id')
            ->toArray();

        if (!empty($itemAllocations)) {
            foreach ($itemAllocations as $projectId => $amount) {
                // Convert to base currency
                $allocations[$projectId] = [
                    'amount' => $this->convertToBaseCurrency($amount, $invoice),
                    'source' => 'line_items',
                ];
            }
            return $allocations;
        }

        // Third, check invoice-level project_id
        if ($invoice->project_id) {
            // Use total_in_base if available, otherwise convert
            $amountInBase = $this->getInvoiceTotalInBase($invoice);
            $allocations[$invoice->project_id] = [
                'amount' => $amountInBase,
                'source' => 'invoice',
            ];
        }

        return $allocations;
    }

    /**
     * Get the exchange rate for an invoice.
     */
    protected function getInvoiceExchangeRate(Invoice $invoice): float
    {
        if ($invoice->currency === 'EGP') {
            return 1.0;
        }
        return $invoice->exchange_rate > 0 ? (float) $invoice->exchange_rate : 1.0;
    }

    /**
     * Convert an amount to base currency (EGP) using invoice exchange rate.
     */
    protected function convertToBaseCurrency(float $amount, Invoice $invoice): float
    {
        if ($invoice->currency === 'EGP') {
            return $amount;
        }
        $exchangeRate = $this->getInvoiceExchangeRate($invoice);
        return round($amount * $exchangeRate, 2);
    }

    /**
     * Get invoice total in base currency (EGP).
     */
    protected function getInvoiceTotalInBase(Invoice $invoice): float
    {
        if ($invoice->currency === 'EGP') {
            return (float) $invoice->total_amount;
        }
        // Use pre-calculated total_in_base if available
        if ($invoice->total_in_base > 0) {
            return (float) $invoice->total_in_base;
        }
        // Otherwise calculate
        return $this->convertToBaseCurrency((float) $invoice->total_amount, $invoice);
    }

    /**
     * Get invoice paid amount in base currency (EGP).
     */
    protected function getInvoicePaidInBase(Invoice $invoice): float
    {
        if ($invoice->currency === 'EGP') {
            return (float) ($invoice->paid_amount ?? 0);
        }
        return $this->convertToBaseCurrency((float) ($invoice->paid_amount ?? 0), $invoice);
    }

    /**
     * Sync a single invoice to a single project.
     */
    public function syncInvoiceToProject(Invoice $invoice, Project $project, float $allocatedAmount): array
    {
        // Calculate proportional tax amount
        $taxAllocation = $this->calculateProportionalTax($invoice, $allocatedAmount);

        // Check if already synced
        $existingRevenue = ProjectRevenue::where('invoice_id', $invoice->id)
            ->where('project_id', $project->id)
            ->first();

        if ($existingRevenue) {
            return $this->updateExistingRevenue($existingRevenue, $invoice, $allocatedAmount, $taxAllocation);
        }

        return $this->createRevenueFromInvoice($invoice, $project, $allocatedAmount, $taxAllocation);
    }

    /**
     * Calculate proportional tax amount for an allocation.
     *
     * The tax is distributed proportionally based on the allocated amount
     * relative to the invoice total (not subtotal, since revenue includes tax).
     */
    protected function calculateProportionalTax(Invoice $invoice, float $allocatedAmount): float
    {
        if ($invoice->tax_amount <= 0 || $invoice->total_amount <= 0) {
            return 0;
        }

        // Get the total in base currency for proportion calculation
        // (allocated amount is already in base currency and includes tax)
        $totalInBase = $this->getInvoiceTotalInBase($invoice);

        if ($totalInBase <= 0) {
            return 0;
        }

        // Calculate the proportion of this allocation to total invoice
        $proportion = $allocatedAmount / $totalInBase;

        // Convert tax to base currency and apply proportion
        $taxInBase = $this->convertToBaseCurrency((float) $invoice->tax_amount, $invoice);

        return round($taxInBase * $proportion, 2);
    }

    /**
     * Create a project revenue from an invoice.
     */
    protected function createRevenueFromInvoice(Invoice $invoice, Project $project, float $allocatedAmount, float $taxAmount = 0): array
    {
        try {
            // Calculate received amount based on payment ratio
            $paidRatio = $invoice->total_amount > 0
                ? ($invoice->paid_amount ?? 0) / $invoice->total_amount
                : 0;
            $allocatedReceived = round($allocatedAmount * $paidRatio, 2);

            $revenue = ProjectRevenue::create([
                'project_id' => $project->id,
                'invoice_id' => $invoice->id,
                'revenue_type' => 'invoice',
                'description' => "Invoice {$invoice->invoice_number}",
                'notes' => $invoice->reference ?? $invoice->notes,
                'amount' => $allocatedAmount,
                'revenue_date' => $invoice->invoice_date ?? now(),
                'due_date' => $invoice->due_date,
                'status' => $this->mapInvoiceStatusToRevenueStatus($invoice->status),
                'amount_received' => $allocatedReceived,
                'received_date' => $invoice->paid_date,
                'synced_from_contract' => false, // This is from invoice, not contract
                'synced_at' => now(),
                'created_by' => auth()->id(),
            ]);

            // Create tax cost if there's tax on the invoice
            $taxCostId = null;
            if ($taxAmount > 0) {
                $taxCost = $this->createTaxCostFromInvoice($invoice, $project, $taxAmount);
                $taxCostId = $taxCost?->id;
            }

            Log::info('Invoice synced to project revenue', [
                'invoice_id' => $invoice->id,
                'project_id' => $project->id,
                'revenue_id' => $revenue->id,
                'allocated_amount' => $allocatedAmount,
                'tax_cost_id' => $taxCostId,
                'tax_amount' => $taxAmount,
            ]);

            return [
                'success' => true,
                'message' => 'Revenue created successfully',
                'revenue_id' => $revenue->id,
                'tax_cost_id' => $taxCostId,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create revenue from invoice', [
                'invoice_id' => $invoice->id,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create revenue: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create a tax cost entry for a project from an invoice.
     */
    protected function createTaxCostFromInvoice(Invoice $invoice, Project $project, float $taxAmount): ?ProjectCost
    {
        // Check if tax cost already exists for this invoice and project
        $existingTaxCost = ProjectCost::where('invoice_id', $invoice->id)
            ->where('project_id', $project->id)
            ->where('cost_type', 'tax')
            ->first();

        if ($existingTaxCost) {
            // Update existing tax cost
            $existingTaxCost->update([
                'amount' => $taxAmount,
                'description' => "VAT/Tax - Invoice {$invoice->invoice_number}",
                'cost_date' => $invoice->invoice_date ?? now(),
            ]);
            return $existingTaxCost;
        }

        // Create new tax cost
        return ProjectCost::create([
            'project_id' => $project->id,
            'invoice_id' => $invoice->id,
            'cost_type' => 'tax',
            'description' => "VAT/Tax - Invoice {$invoice->invoice_number}",
            'amount' => $taxAmount,
            'cost_date' => $invoice->invoice_date ?? now(),
            'is_billable' => false,
            'is_auto_generated' => true,
            'reference_type' => 'invoice_tax',
            'reference_id' => $invoice->id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update an existing project revenue from invoice.
     */
    protected function updateExistingRevenue(ProjectRevenue $revenue, Invoice $invoice, float $allocatedAmount, float $taxAmount = 0): array
    {
        try {
            // Calculate received amount based on payment ratio
            $paidRatio = $invoice->total_amount > 0
                ? ($invoice->paid_amount ?? 0) / $invoice->total_amount
                : 0;
            $allocatedReceived = round($allocatedAmount * $paidRatio, 2);

            $revenue->update([
                'description' => "Invoice {$invoice->invoice_number}",
                'notes' => $invoice->reference ?? $invoice->notes,
                'amount' => $allocatedAmount,
                'due_date' => $invoice->due_date,
                'status' => $this->mapInvoiceStatusToRevenueStatus($invoice->status),
                'amount_received' => $allocatedReceived,
                'received_date' => $invoice->paid_date,
                'synced_at' => now(),
            ]);

            // Update or create tax cost
            $taxCostId = null;
            if ($taxAmount > 0) {
                $project = Project::find($revenue->project_id);
                if ($project) {
                    $taxCost = $this->createTaxCostFromInvoice($invoice, $project, $taxAmount);
                    $taxCostId = $taxCost?->id;
                }
            } else {
                // Remove tax cost if tax is now zero
                ProjectCost::where('invoice_id', $invoice->id)
                    ->where('project_id', $revenue->project_id)
                    ->where('cost_type', 'tax')
                    ->delete();
            }

            return [
                'success' => true,
                'message' => 'Revenue updated successfully',
                'revenue_id' => $revenue->id,
                'tax_cost_id' => $taxCostId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update revenue: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Map invoice status to project revenue status.
     */
    protected function mapInvoiceStatusToRevenueStatus(string $invoiceStatus): string
    {
        return match ($invoiceStatus) {
            'draft' => 'planned',
            'sent' => 'invoiced',
            'paid' => 'received',
            'partial' => 'partial',
            'overdue' => 'overdue',
            'cancelled' => 'planned',
            default => 'invoiced',
        };
    }

    /**
     * Sync invoice status change to project revenues.
     *
     * Call this when an invoice status changes (e.g., marked as paid).
     */
    public function syncInvoiceStatusChange(Invoice $invoice): array
    {
        $revenues = ProjectRevenue::where('invoice_id', $invoice->id)->get();

        if ($revenues->isEmpty()) {
            return [
                'success' => true,
                'message' => 'Invoice not synced to any projects',
            ];
        }

        $updatedCount = 0;
        $errors = [];

        // Calculate payment ratio
        $paidRatio = $invoice->total_amount > 0
            ? ($invoice->paid_amount ?? 0) / $invoice->total_amount
            : 0;

        foreach ($revenues as $revenue) {
            try {
                $allocatedReceived = round($revenue->amount * $paidRatio, 2);

                $revenue->update([
                    'status' => $this->mapInvoiceStatusToRevenueStatus($invoice->status),
                    'amount_received' => $allocatedReceived,
                    'received_date' => $invoice->paid_date,
                    'synced_at' => now(),
                ]);

                $updatedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to update revenue {$revenue->id}: " . $e->getMessage();
            }
        }

        return [
            'success' => count($errors) === 0,
            'message' => "Updated {$updatedCount} revenue(s)" . (count($errors) > 0 ? ", " . count($errors) . " error(s)" : ''),
            'updated' => $updatedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Handle invoice being paid.
     */
    public function onInvoicePaid(Invoice $invoice): array
    {
        // First, sync if not already synced
        $allocations = $this->getProjectAllocations($invoice);

        if (!empty($allocations)) {
            $this->syncInvoiceToProjects($invoice);
        }

        // Then update status
        return $this->syncInvoiceStatusChange($invoice);
    }

    /**
     * Remove synced revenues and tax costs when invoice is deleted or cancelled.
     */
    public function removeInvoiceRevenues(Invoice $invoice): array
    {
        try {
            $revenueCount = ProjectRevenue::where('invoice_id', $invoice->id)->delete();
            $taxCostCount = ProjectCost::where('invoice_id', $invoice->id)
                ->where('cost_type', 'tax')
                ->delete();

            return [
                'success' => true,
                'message' => "Removed {$revenueCount} revenue(s) and {$taxCostCount} tax cost(s)",
                'removed_revenues' => $revenueCount,
                'removed_tax_costs' => $taxCostCount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to remove revenues: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get sync status for an invoice.
     */
    public function getInvoiceSyncStatus(Invoice $invoice): array
    {
        $allocations = $this->getProjectAllocations($invoice);
        $syncedRevenues = ProjectRevenue::where('invoice_id', $invoice->id)->get();

        $projects = [];
        foreach ($syncedRevenues as $revenue) {
            $projects[$revenue->project_id] = [
                'name' => $revenue->project?->name ?? 'Unknown',
                'amount' => $revenue->amount,
                'status' => $revenue->status,
                'synced_at' => $revenue->synced_at,
            ];
        }

        return [
            'has_allocations' => !empty($allocations),
            'allocation_count' => count($allocations),
            'synced_count' => $syncedRevenues->count(),
            'total_allocated' => collect($allocations)->sum('amount'),
            'total_synced' => $syncedRevenues->sum('amount'),
            'projects' => $projects,
        ];
    }

    /**
     * Bulk sync all invoices with project allocations.
     */
    public function bulkSyncAllInvoices(): array
    {
        $invoices = Invoice::with(['projects', 'items'])->get();
        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($invoices as $invoice) {
            $allocations = $this->getProjectAllocations($invoice);

            if (empty($allocations)) {
                continue;
            }

            $result = $this->syncInvoiceToProjects($invoice);
            $totalSynced += $result['synced'];
            if (!$result['success']) {
                $totalErrors++;
            }
        }

        return [
            'success' => $totalErrors === 0,
            'message' => "Bulk sync complete: synced to {$totalSynced} projects",
            'total_synced' => $totalSynced,
            'invoices_with_errors' => $totalErrors,
        ];
    }

    /**
     * Sync tax costs for all existing invoice-project links.
     *
     * This method is used to backfill tax costs for invoices that were
     * already synced to projects before the tax cost feature was added.
     * It recalculates all tax costs to ensure accuracy.
     */
    public function syncTaxCostsForExistingLinks(): array
    {
        $revenues = ProjectRevenue::whereNotNull('invoice_id')
            ->where('revenue_type', 'invoice')
            ->with(['invoice', 'project'])
            ->get();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $deleted = 0;
        $errors = [];

        foreach ($revenues as $revenue) {
            if (!$revenue->invoice || !$revenue->project) {
                $skipped++;
                continue;
            }

            $invoice = $revenue->invoice;

            // Check if tax cost already exists
            $existingTaxCost = ProjectCost::where('invoice_id', $invoice->id)
                ->where('project_id', $revenue->project_id)
                ->where('cost_type', 'tax')
                ->first();

            // Skip if no tax on invoice - but delete existing tax cost if any
            if ($invoice->tax_amount <= 0) {
                if ($existingTaxCost) {
                    $existingTaxCost->delete();
                    $deleted++;
                } else {
                    $skipped++;
                }
                continue;
            }

            try {
                // Calculate proportional tax for this allocation
                $taxAmount = $this->calculateProportionalTax($invoice, $revenue->amount);

                if ($taxAmount <= 0) {
                    if ($existingTaxCost) {
                        $existingTaxCost->delete();
                        $deleted++;
                    } else {
                        $skipped++;
                    }
                    continue;
                }

                if ($existingTaxCost) {
                    $existingTaxCost->update([
                        'amount' => $taxAmount,
                        'description' => "VAT/Tax - Invoice {$invoice->invoice_number}",
                        'cost_date' => $invoice->invoice_date ?? now(),
                    ]);
                    $updated++;
                } else {
                    ProjectCost::create([
                        'project_id' => $revenue->project_id,
                        'invoice_id' => $invoice->id,
                        'cost_type' => 'tax',
                        'description' => "VAT/Tax - Invoice {$invoice->invoice_number}",
                        'amount' => $taxAmount,
                        'cost_date' => $invoice->invoice_date ?? now(),
                        'is_billable' => false,
                        'is_auto_generated' => true,
                        'reference_type' => 'invoice_tax',
                        'reference_id' => $invoice->id,
                        'created_by' => auth()->id() ?? 1,
                    ]);
                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = "Invoice {$invoice->id}: " . $e->getMessage();
            }
        }

        return [
            'success' => count($errors) === 0,
            'message' => "Tax costs sync complete: {$created} created, {$updated} updated, {$deleted} deleted, {$skipped} skipped",
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
