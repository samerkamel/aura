<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectCost;
use Modules\Project\Models\ProjectBudget;

/**
 * Service for syncing expenses to project costs.
 *
 * This service provides synchronization between:
 * - Expense schedules (Accounting module)
 * - Project costs (Project module)
 *
 * When an expense is linked to a project, it creates/updates
 * a ProjectCost entry for accurate project financial tracking.
 */
class ExpenseProjectSyncService
{
    /**
     * Sync an expense to its linked project as a cost entry.
     */
    public function syncExpenseToProjectCost(ExpenseSchedule $expense): array
    {
        if (!$expense->project_id) {
            return [
                'success' => false,
                'message' => 'Expense has no linked project',
            ];
        }

        $project = Project::find($expense->project_id);
        if (!$project) {
            return [
                'success' => false,
                'message' => 'Project not found',
            ];
        }

        // Check if already synced
        $existingCost = ProjectCost::where('expense_schedule_id', $expense->id)->first();

        if ($existingCost) {
            return $this->updateExistingCost($existingCost, $expense);
        }

        return $this->createCostFromExpense($expense, $project);
    }

    /**
     * Create a project cost from an expense schedule.
     */
    protected function createCostFromExpense(ExpenseSchedule $expense, Project $project): array
    {
        try {
            // Try to find a matching budget category
            $budgetId = $this->findMatchingBudget($expense, $project);

            // Map expense category to cost type
            $costType = $this->mapExpenseToCostType($expense);

            $cost = ProjectCost::create([
                'project_id' => $project->id,
                'project_budget_id' => $budgetId,
                'expense_schedule_id' => $expense->id,
                'cost_type' => $costType,
                'description' => $expense->name,
                'notes' => $expense->description,
                'amount' => $expense->amount,
                'cost_date' => $expense->expense_date ?? $expense->start_date ?? now(),
                'is_billable' => true,
                'is_auto_generated' => true,
                'reference_type' => 'expense_schedule',
                'reference_id' => $expense->id,
                'synced_to_accounting' => true,
                'synced_at' => now(),
                'created_by' => auth()->id(),
            ]);

            // Update expense with cost link
            $expense->update([
                'project_cost_id' => $cost->id,
                'is_project_expense' => true,
            ]);

            Log::info('Expense synced to project cost', [
                'expense_id' => $expense->id,
                'project_id' => $project->id,
                'cost_id' => $cost->id,
            ]);

            return [
                'success' => true,
                'message' => 'Cost created successfully',
                'cost_id' => $cost->id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create project cost from expense', [
                'expense_id' => $expense->id,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create cost: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing project cost from expense.
     */
    protected function updateExistingCost(ProjectCost $cost, ExpenseSchedule $expense): array
    {
        try {
            $cost->update([
                'description' => $expense->name,
                'notes' => $expense->description,
                'amount' => $expense->amount,
                'cost_date' => $expense->expense_date ?? $expense->start_date ?? $cost->cost_date,
                'synced_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Cost updated successfully',
                'cost_id' => $cost->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update cost: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find a matching project budget for the expense category.
     */
    protected function findMatchingBudget(ExpenseSchedule $expense, Project $project): ?int
    {
        // Try to match by expense category name
        $categoryName = $expense->category?->name;

        if (!$categoryName) {
            return null;
        }

        // Look for a budget with a matching name
        $budget = ProjectBudget::where('project_id', $project->id)
            ->where(function ($query) use ($categoryName) {
                $query->where('category', 'like', '%' . $categoryName . '%')
                    ->orWhere('category', 'like', '%expense%')
                    ->orWhere('category', 'like', '%other%');
            })
            ->first();

        return $budget?->id;
    }

    /**
     * Map expense category to cost type.
     */
    protected function mapExpenseToCostType(ExpenseSchedule $expense): string
    {
        $categoryName = strtolower($expense->category?->name ?? '');

        // Match against known cost types
        if (str_contains($categoryName, 'software') || str_contains($categoryName, 'license')) {
            return 'software';
        }

        if (str_contains($categoryName, 'infrastructure') || str_contains($categoryName, 'server') || str_contains($categoryName, 'hosting')) {
            return 'infrastructure';
        }

        if (str_contains($categoryName, 'contractor') || str_contains($categoryName, 'freelance')) {
            return 'contractor';
        }

        if (str_contains($categoryName, 'salary') || str_contains($categoryName, 'labor') || str_contains($categoryName, 'wages')) {
            return 'labor';
        }

        // Default to expense
        return 'expense';
    }

    /**
     * Remove project cost when expense is deleted.
     */
    public function removeExpenseCost(ExpenseSchedule $expense): array
    {
        try {
            $count = ProjectCost::where('expense_schedule_id', $expense->id)->delete();

            return [
                'success' => true,
                'message' => "Removed {$count} cost entry",
                'removed' => $count,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to remove cost: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle expense payment - update cost amount if needed.
     */
    public function onExpensePaid(ExpenseSchedule $expense): array
    {
        if (!$expense->project_cost_id) {
            // Try to sync if not already synced
            if ($expense->project_id) {
                return $this->syncExpenseToProjectCost($expense);
            }
            return [
                'success' => true,
                'message' => 'Expense not linked to project',
            ];
        }

        $cost = ProjectCost::find($expense->project_cost_id);

        if (!$cost) {
            return [
                'success' => false,
                'message' => 'Linked cost not found',
            ];
        }

        try {
            // Update with actual paid amount if different
            $cost->update([
                'amount' => $expense->paid_amount ?? $expense->amount,
                'synced_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Cost amount updated',
                'cost_id' => $cost->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update cost: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle project unlinking from expense.
     */
    public function onProjectUnlinked(ExpenseSchedule $expense, int $previousProjectId): array
    {
        try {
            // Delete the cost entry
            $count = ProjectCost::where('expense_schedule_id', $expense->id)
                ->where('project_id', $previousProjectId)
                ->delete();

            // Clear the expense's project_cost_id
            $expense->update([
                'project_cost_id' => null,
                'is_project_expense' => false,
            ]);

            return [
                'success' => true,
                'message' => "Removed {$count} cost entry",
                'removed' => $count,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to remove cost: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get sync status for an expense.
     */
    public function getExpenseSyncStatus(ExpenseSchedule $expense): array
    {
        if (!$expense->project_id) {
            return [
                'linked_to_project' => false,
                'synced' => false,
            ];
        }

        $cost = ProjectCost::where('expense_schedule_id', $expense->id)->first();

        return [
            'linked_to_project' => true,
            'project_id' => $expense->project_id,
            'project_name' => $expense->project?->name,
            'synced' => $cost !== null,
            'cost_id' => $cost?->id,
            'cost_amount' => $cost?->amount,
            'synced_at' => $cost?->synced_at,
        ];
    }

    /**
     * Bulk sync all expenses with projects.
     */
    public function bulkSyncAllExpenses(): array
    {
        $expenses = ExpenseSchedule::whereNotNull('project_id')
            ->whereNull('project_cost_id')
            ->get();

        $synced = 0;
        $errors = 0;

        foreach ($expenses as $expense) {
            $result = $this->syncExpenseToProjectCost($expense);
            if ($result['success']) {
                $synced++;
            } else {
                $errors++;
            }
        }

        return [
            'success' => $errors === 0,
            'message' => "Synced {$synced} expense(s), {$errors} error(s)",
            'synced' => $synced,
            'errors' => $errors,
        ];
    }
}
