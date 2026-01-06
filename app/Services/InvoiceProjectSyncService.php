<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Invoicing\Models\Invoice;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectRevenue;

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
     * @return array<int, array{amount: float, source: string}>
     */
    protected function getProjectAllocations(Invoice $invoice): array
    {
        $allocations = [];

        // First, check invoice_project pivot table
        if ($invoice->projects->isNotEmpty()) {
            foreach ($invoice->projects as $project) {
                $allocations[$project->id] = [
                    'amount' => $project->pivot->allocated_amount ?? 0,
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
                $allocations[$projectId] = [
                    'amount' => $amount,
                    'source' => 'line_items',
                ];
            }
            return $allocations;
        }

        // Third, check invoice-level project_id
        if ($invoice->project_id) {
            $allocations[$invoice->project_id] = [
                'amount' => $invoice->total_amount,
                'source' => 'invoice',
            ];
        }

        return $allocations;
    }

    /**
     * Sync a single invoice to a single project.
     */
    public function syncInvoiceToProject(Invoice $invoice, Project $project, float $allocatedAmount): array
    {
        // Check if already synced
        $existingRevenue = ProjectRevenue::where('invoice_id', $invoice->id)
            ->where('project_id', $project->id)
            ->first();

        if ($existingRevenue) {
            return $this->updateExistingRevenue($existingRevenue, $invoice, $allocatedAmount);
        }

        return $this->createRevenueFromInvoice($invoice, $project, $allocatedAmount);
    }

    /**
     * Create a project revenue from an invoice.
     */
    protected function createRevenueFromInvoice(Invoice $invoice, Project $project, float $allocatedAmount): array
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

            Log::info('Invoice synced to project revenue', [
                'invoice_id' => $invoice->id,
                'project_id' => $project->id,
                'revenue_id' => $revenue->id,
                'allocated_amount' => $allocatedAmount,
            ]);

            return [
                'success' => true,
                'message' => 'Revenue created successfully',
                'revenue_id' => $revenue->id,
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
     * Update an existing project revenue from invoice.
     */
    protected function updateExistingRevenue(ProjectRevenue $revenue, Invoice $invoice, float $allocatedAmount): array
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

            return [
                'success' => true,
                'message' => 'Revenue updated successfully',
                'revenue_id' => $revenue->id,
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
     * Remove synced revenues when invoice is deleted or cancelled.
     */
    public function removeInvoiceRevenues(Invoice $invoice): array
    {
        try {
            $count = ProjectRevenue::where('invoice_id', $invoice->id)->delete();

            return [
                'success' => true,
                'message' => "Removed {$count} revenue(s)",
                'removed' => $count,
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
}
