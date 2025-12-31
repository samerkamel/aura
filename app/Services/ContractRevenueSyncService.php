<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectRevenue;

/**
 * Service for syncing contract payments to project revenues.
 *
 * This service provides bidirectional synchronization between:
 * - Contract payments (Accounting module)
 * - Project revenues (Project module)
 *
 * When a contract is linked to a project, contract payments can be
 * synced as project revenues for accurate project financial tracking.
 */
class ContractRevenueSyncService
{
    /**
     * Sync all payments from a contract to its linked projects.
     *
     * Creates project revenue entries for each contract payment
     * for all projects linked to the contract.
     */
    public function syncContractToProjects(Contract $contract): array
    {
        $projects = $contract->projects;

        if ($projects->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Contract has no linked projects',
                'synced' => 0,
            ];
        }

        $synced = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($projects as $project) {
                foreach ($contract->payments as $payment) {
                    $result = $this->syncPaymentToProject($payment, $project);
                    if ($result['success']) {
                        $synced++;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }

            DB::commit();

            return [
                'success' => count($errors) === 0,
                'message' => "Synced {$synced} payment(s)" . (count($errors) > 0 ? ", " . count($errors) . " error(s)" : ''),
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contract revenue sync failed', [
                'contract_id' => $contract->id,
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
     * Sync a single contract payment to a project as revenue.
     */
    public function syncPaymentToProject(ContractPayment $payment, Project $project): array
    {
        // Check if already synced
        $existingRevenue = ProjectRevenue::where('contract_payment_id', $payment->id)
            ->where('project_id', $project->id)
            ->first();

        if ($existingRevenue) {
            // Update existing revenue
            return $this->updateExistingRevenue($existingRevenue, $payment);
        }

        // Create new revenue
        return $this->createRevenueFromPayment($payment, $project);
    }

    /**
     * Create a project revenue from a contract payment.
     */
    protected function createRevenueFromPayment(ContractPayment $payment, Project $project): array
    {
        try {
            $revenue = ProjectRevenue::create([
                'project_id' => $project->id,
                'contract_id' => $payment->contract_id,
                'contract_payment_id' => $payment->id,
                'revenue_type' => $payment->is_milestone ? 'milestone' : 'contract',
                'description' => $payment->name,
                'notes' => $payment->description,
                'amount' => $payment->amount,
                'revenue_date' => $payment->due_date ?? now(),
                'due_date' => $payment->due_date,
                'status' => $this->mapPaymentStatusToRevenueStatus($payment->status),
                'amount_received' => $payment->paid_amount ?? 0,
                'received_date' => $payment->paid_date,
                'synced_from_contract' => true,
                'synced_at' => now(),
                'created_by' => auth()->id(),
            ]);

            // Update payment with revenue link
            $payment->update([
                'project_revenue_id' => $revenue->id,
                'synced_to_project' => true,
                'synced_to_project_at' => now(),
            ]);

            Log::info('Contract payment synced to project revenue', [
                'payment_id' => $payment->id,
                'project_id' => $project->id,
                'revenue_id' => $revenue->id,
            ]);

            return [
                'success' => true,
                'message' => 'Revenue created successfully',
                'revenue_id' => $revenue->id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create revenue from payment', [
                'payment_id' => $payment->id,
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
     * Update an existing project revenue from contract payment.
     */
    protected function updateExistingRevenue(ProjectRevenue $revenue, ContractPayment $payment): array
    {
        try {
            $revenue->update([
                'revenue_type' => $payment->is_milestone ? 'milestone' : 'contract',
                'description' => $payment->name,
                'notes' => $payment->description,
                'amount' => $payment->amount,
                'due_date' => $payment->due_date,
                'status' => $this->mapPaymentStatusToRevenueStatus($payment->status),
                'amount_received' => $payment->paid_amount ?? 0,
                'received_date' => $payment->paid_date,
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
     * Map contract payment status to project revenue status.
     */
    protected function mapPaymentStatusToRevenueStatus(string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'paid' => 'received',
            'overdue' => 'overdue',
            'cancelled' => 'planned',
            default => 'planned',
        };
    }

    /**
     * Sync payment status change to project revenue.
     *
     * Call this when a contract payment status changes.
     */
    public function syncPaymentStatusChange(ContractPayment $payment): array
    {
        if (!$payment->project_revenue_id) {
            // Not synced to any project revenue yet
            return [
                'success' => true,
                'message' => 'Payment not synced to project',
            ];
        }

        $revenue = ProjectRevenue::find($payment->project_revenue_id);

        if (!$revenue) {
            return [
                'success' => false,
                'message' => 'Linked revenue not found',
            ];
        }

        try {
            $revenue->update([
                'status' => $this->mapPaymentStatusToRevenueStatus($payment->status),
                'amount_received' => $payment->paid_amount ?? 0,
                'received_date' => $payment->paid_date,
                'synced_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Revenue status synced',
                'revenue_id' => $revenue->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to sync status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync all payments for a contract to all linked projects when payment is marked as paid.
     */
    public function onPaymentPaid(ContractPayment $payment): array
    {
        // Update the linked revenue if exists
        $statusResult = $this->syncPaymentStatusChange($payment);

        // If not synced yet, try to sync now
        if (!$payment->synced_to_project) {
            $contract = $payment->contract;
            $projects = $contract->projects;

            foreach ($projects as $project) {
                $this->syncPaymentToProject($payment, $project);
            }
        }

        return $statusResult;
    }

    /**
     * When a project is linked to a contract, sync all contract payments.
     */
    public function onProjectLinkedToContract(Project $project, Contract $contract): array
    {
        $synced = 0;
        $errors = [];

        foreach ($contract->payments as $payment) {
            $result = $this->syncPaymentToProject($payment, $project);
            if ($result['success']) {
                $synced++;
            } else {
                $errors[] = $result['message'];
            }
        }

        return [
            'success' => count($errors) === 0,
            'message' => "Synced {$synced} payment(s) to project",
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    /**
     * Remove synced revenues when a project is unlinked from a contract.
     */
    public function onProjectUnlinkedFromContract(Project $project, Contract $contract): array
    {
        try {
            $revenues = ProjectRevenue::where('project_id', $project->id)
                ->where('contract_id', $contract->id)
                ->where('synced_from_contract', true)
                ->get();

            $count = $revenues->count();

            // Update contract payments to remove revenue links
            foreach ($revenues as $revenue) {
                ContractPayment::where('project_revenue_id', $revenue->id)
                    ->update([
                        'project_revenue_id' => null,
                        'synced_to_project' => false,
                        'synced_to_project_at' => null,
                    ]);
            }

            // Delete the synced revenues
            ProjectRevenue::where('project_id', $project->id)
                ->where('contract_id', $contract->id)
                ->where('synced_from_contract', true)
                ->delete();

            return [
                'success' => true,
                'message' => "Removed {$count} synced revenue(s)",
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
     * Get sync status for a contract.
     */
    public function getContractSyncStatus(Contract $contract): array
    {
        $totalPayments = $contract->payments()->count();
        $syncedPayments = $contract->payments()->where('synced_to_project', true)->count();
        $projects = $contract->projects->pluck('name', 'id');

        return [
            'total_payments' => $totalPayments,
            'synced_payments' => $syncedPayments,
            'unsynced_payments' => $totalPayments - $syncedPayments,
            'linked_projects' => $projects,
            'sync_percentage' => $totalPayments > 0
                ? round(($syncedPayments / $totalPayments) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get sync status for a project.
     */
    public function getProjectRevenueFromContracts(Project $project): array
    {
        $revenues = $project->revenues()
            ->where('synced_from_contract', true)
            ->with(['contract'])
            ->get();

        return [
            'total_synced_revenues' => $revenues->count(),
            'total_amount' => $revenues->sum('amount'),
            'total_received' => $revenues->sum('amount_received'),
            'by_contract' => $revenues->groupBy('contract_id')->map(function ($group) {
                return [
                    'contract_name' => $group->first()->contract?->client_name ?? 'Unknown',
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                    'received' => $group->sum('amount_received'),
                ];
            }),
        ];
    }

    /**
     * Bulk sync all contracts to their linked projects.
     */
    public function bulkSyncAllContracts(): array
    {
        $contracts = Contract::with(['projects', 'payments'])->get();
        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($contracts as $contract) {
            if ($contract->projects->isEmpty()) {
                continue;
            }

            $result = $this->syncContractToProjects($contract);
            $totalSynced += $result['synced'];
            if (!$result['success']) {
                $totalErrors++;
            }
        }

        return [
            'success' => $totalErrors === 0,
            'message' => "Bulk sync complete: {$totalSynced} payments synced",
            'total_synced' => $totalSynced,
            'contracts_with_errors' => $totalErrors,
        ];
    }
}
