<?php

namespace Modules\Accounting\Observers;

use App\Services\ExpenseProjectSyncService;
use Modules\Accounting\Models\ExpenseSchedule;
use Illuminate\Support\Facades\Log;

/**
 * Observer for ExpenseSchedule model events.
 *
 * Automatically syncs expenses to project costs when:
 * - An expense is created with a project link
 * - An expense is updated (project link changed or amounts changed)
 * - An expense is deleted
 */
class ExpenseScheduleObserver
{
    protected ExpenseProjectSyncService $syncService;

    public function __construct(ExpenseProjectSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the ExpenseSchedule "created" event.
     */
    public function created(ExpenseSchedule $expense): void
    {
        // Only sync if expense has a project and auto-sync is enabled
        if ($expense->project_id && $expense->is_project_expense) {
            $this->syncExpenseToProject($expense, 'created');
        }
    }

    /**
     * Handle the ExpenseSchedule "updated" event.
     */
    public function updated(ExpenseSchedule $expense): void
    {
        $previousProjectId = $expense->getOriginal('project_id');
        $currentProjectId = $expense->project_id;

        // Check if project was unlinked
        if ($previousProjectId && !$currentProjectId) {
            $this->syncService->onProjectUnlinked($expense, $previousProjectId);
            return;
        }

        // Check if project was linked or changed
        if ($currentProjectId) {
            // If project changed, remove old cost first
            if ($previousProjectId && $previousProjectId !== $currentProjectId) {
                $this->syncService->onProjectUnlinked($expense, $previousProjectId);
            }

            // If expense is marked as project expense and has project, sync
            if ($expense->is_project_expense) {
                $this->syncExpenseToProject($expense, 'updated');
            }
        }

        // Check if payment status changed to paid
        if ($expense->wasChanged('payment_status') && $expense->payment_status === 'paid') {
            $this->syncService->onExpensePaid($expense);
        }

        // If amount changed and already synced, update the cost
        if ($expense->wasChanged('amount') && $expense->project_cost_id) {
            $this->syncService->syncExpenseToProjectCost($expense);
        }
    }

    /**
     * Handle the ExpenseSchedule "deleted" event.
     */
    public function deleted(ExpenseSchedule $expense): void
    {
        $this->deleteLinkedCost($expense);
    }

    /**
     * Sync an expense to its linked project.
     */
    protected function syncExpenseToProject(ExpenseSchedule $expense, string $action): void
    {
        try {
            $result = $this->syncService->syncExpenseToProjectCost($expense);

            if ($result['success']) {
                Log::info("Expense {$expense->id} synced to project cost ({$action})", [
                    'expense_id' => $expense->id,
                    'project_id' => $expense->project_id,
                    'cost_id' => $result['cost_id'] ?? null,
                ]);
            } else {
                Log::warning("Failed to sync expense {$expense->id} to project", [
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error syncing expense {$expense->id} to project", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete project cost linked to this expense.
     */
    protected function deleteLinkedCost(ExpenseSchedule $expense): void
    {
        try {
            $result = $this->syncService->removeExpenseCost($expense);

            if ($result['removed'] > 0) {
                Log::info("Deleted {$result['removed']} project cost(s) for deleted expense {$expense->id}");
            }
        } catch (\Exception $e) {
            Log::error("Error deleting cost for expense {$expense->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
