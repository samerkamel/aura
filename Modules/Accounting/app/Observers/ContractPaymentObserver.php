<?php

namespace Modules\Accounting\Observers;

use App\Services\ContractRevenueSyncService;
use Modules\Accounting\Models\ContractPayment;
use Illuminate\Support\Facades\Log;

/**
 * Observer for ContractPayment model events.
 *
 * Automatically syncs contract payments to project revenues when:
 * - A payment is created
 * - A payment is updated (especially status changes)
 * - A payment is deleted
 */
class ContractPaymentObserver
{
    protected ContractRevenueSyncService $syncService;

    public function __construct(ContractRevenueSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the ContractPayment "created" event.
     */
    public function created(ContractPayment $payment): void
    {
        $this->syncPaymentToProjects($payment, 'created');
    }

    /**
     * Handle the ContractPayment "updated" event.
     */
    public function updated(ContractPayment $payment): void
    {
        // Check if status changed to paid
        if ($payment->wasChanged('status') && $payment->status === 'paid') {
            $this->syncService->onPaymentPaid($payment);
            return;
        }

        // For other updates, sync to all linked projects
        $this->syncPaymentToProjects($payment, 'updated');
    }

    /**
     * Handle the ContractPayment "deleted" event.
     */
    public function deleted(ContractPayment $payment): void
    {
        $this->deleteLinkedRevenues($payment);
    }

    /**
     * Sync a payment to all projects linked to its contract.
     */
    protected function syncPaymentToProjects(ContractPayment $payment, string $action): void
    {
        $contract = $payment->contract;

        if (!$contract) {
            Log::warning("ContractPayment {$payment->id} has no contract, skipping sync");
            return;
        }

        $projects = $contract->projects;

        if ($projects->isEmpty()) {
            // No projects linked, nothing to sync
            return;
        }

        foreach ($projects as $project) {
            try {
                $result = $this->syncService->syncPaymentToProject($payment, $project);

                if ($result['success']) {
                    Log::info("Payment {$payment->id} synced to project {$project->id} ({$action})", [
                        'payment_id' => $payment->id,
                        'project_id' => $project->id,
                        'revenue_id' => $result['revenue_id'] ?? null,
                    ]);
                } else {
                    Log::warning("Failed to sync payment {$payment->id} to project {$project->id}", [
                        'message' => $result['message'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Error syncing payment {$payment->id} to project {$project->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Delete project revenues linked to this payment.
     */
    protected function deleteLinkedRevenues(ContractPayment $payment): void
    {
        try {
            // Delete all project revenues linked to this payment
            $deleted = \Modules\Project\Models\ProjectRevenue::where('contract_payment_id', $payment->id)->delete();

            if ($deleted > 0) {
                Log::info("Deleted {$deleted} project revenue(s) for deleted payment {$payment->id}");
            }
        } catch (\Exception $e) {
            Log::error("Error deleting revenues for payment {$payment->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
