<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectCost;

/**
 * Service for syncing project costs to accounting expenses.
 *
 * This service handles:
 * 1. Creating expense entries when project costs are recorded
 * 2. Syncing existing project costs to accounting
 * 3. Providing summary data for project-accounting integration
 */
class ProjectAccountingSyncService
{
    /**
     * The project expense category.
     */
    protected ?ExpenseCategory $projectCategory = null;

    /**
     * Get or create the Project Expense category.
     */
    public function getProjectCategory(): ExpenseCategory
    {
        if ($this->projectCategory) {
            return $this->projectCategory;
        }

        $this->projectCategory = ExpenseCategory::firstOrCreate(
            ['name' => 'Project Expense'],
            [
                'name_ar' => 'مصروفات المشاريع',
                'description' => 'Expenses automatically synced from project costs',
                'color' => '#2196F3',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        return $this->projectCategory;
    }

    /**
     * Sync a single project cost to accounting.
     *
     * @param ProjectCost $cost The project cost to sync
     * @return array Sync result
     */
    public function syncCost(ProjectCost $cost): array
    {
        if ($cost->synced_to_accounting) {
            return [
                'success' => false,
                'message' => 'Cost already synced to accounting',
            ];
        }

        $category = $this->getProjectCategory();

        DB::beginTransaction();

        try {
            // Create expense schedule entry
            $expense = ExpenseSchedule::create([
                'category_id' => $category->id,
                'project_id' => $cost->project_id,
                'project_cost_id' => $cost->id,
                'is_project_expense' => true,
                'name' => $this->generateExpenseName($cost),
                'description' => $cost->description,
                'amount' => $cost->amount,
                'frequency_type' => 'monthly',
                'frequency_value' => 1,
                'start_date' => $cost->cost_date,
                'end_date' => $cost->cost_date,
                'expense_type' => 'one_time',
                'expense_date' => $cost->cost_date,
                'is_active' => true,
                'payment_status' => 'pending',
            ]);

            // Update cost with sync status
            $cost->update([
                'expense_schedule_id' => $expense->id,
                'synced_to_accounting' => true,
                'synced_at' => now(),
            ]);

            DB::commit();

            Log::info('Project cost synced to accounting', [
                'project_cost_id' => $cost->id,
                'expense_schedule_id' => $expense->id,
                'amount' => $cost->amount,
            ]);

            return [
                'success' => true,
                'message' => 'Cost synced to accounting',
                'expense_id' => $expense->id,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to sync project cost to accounting', [
                'project_cost_id' => $cost->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate expense name from project cost.
     */
    private function generateExpenseName(ProjectCost $cost): string
    {
        $projectName = $cost->project?->name ?? 'Unknown Project';
        $costType = $cost->cost_type_label;

        return "{$projectName} - {$costType}";
    }

    /**
     * Sync all unsynced costs for a project.
     *
     * @param Project $project The project to sync
     * @return array Sync summary
     */
    public function syncProjectCosts(Project $project): array
    {
        $unsyncedCosts = $project->costs()
            ->where('synced_to_accounting', false)
            ->get();

        if ($unsyncedCosts->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No unsynced costs found',
                'synced' => 0,
                'total_amount' => 0,
            ];
        }

        $synced = 0;
        $failed = 0;
        $totalAmount = 0;

        foreach ($unsyncedCosts as $cost) {
            $result = $this->syncCost($cost);

            if ($result['success']) {
                $synced++;
                $totalAmount += $cost->amount;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "Synced {$synced} costs" . ($failed > 0 ? ", {$failed} failed" : ''),
            'synced' => $synced,
            'failed' => $failed,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Get expenses linked to a project.
     *
     * @param Project $project The project
     * @return Collection
     */
    public function getProjectExpenses(Project $project): Collection
    {
        return ExpenseSchedule::where('project_id', $project->id)
            ->where('is_project_expense', true)
            ->with(['category', 'paidFromAccount'])
            ->orderByDesc('expense_date')
            ->get();
    }

    /**
     * Get project expenses summary.
     *
     * @param Project $project The project
     * @return array
     */
    public function getProjectExpensesSummary(Project $project): array
    {
        $expenses = $this->getProjectExpenses($project);

        return [
            'total_count' => $expenses->count(),
            'total_amount' => $expenses->sum('amount'),
            'paid_count' => $expenses->where('payment_status', 'paid')->count(),
            'paid_amount' => $expenses->where('payment_status', 'paid')->sum('paid_amount'),
            'pending_count' => $expenses->where('payment_status', 'pending')->count(),
            'pending_amount' => $expenses->where('payment_status', 'pending')->sum('amount'),
            'synced_costs' => $project->costs()->where('synced_to_accounting', true)->count(),
            'unsynced_costs' => $project->costs()->where('synced_to_accounting', false)->count(),
        ];
    }

    /**
     * Mark project expense as paid.
     *
     * @param ExpenseSchedule $expense The expense to mark as paid
     * @param float $amount The amount paid
     * @param int|null $accountId The account used for payment
     * @param string|null $notes Payment notes
     * @return array Result
     */
    public function markExpenseAsPaid(
        ExpenseSchedule $expense,
        float $amount,
        ?int $accountId = null,
        ?string $notes = null
    ): array {
        if (!$expense->is_project_expense) {
            return [
                'success' => false,
                'message' => 'This is not a project expense',
            ];
        }

        try {
            $expense->update([
                'payment_status' => 'paid',
                'paid_amount' => $amount,
                'paid_from_account_id' => $accountId,
                'paid_date' => now(),
                'payment_notes' => $notes,
            ]);

            Log::info('Project expense marked as paid', [
                'expense_id' => $expense->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'message' => 'Expense marked as paid',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to mark project expense as paid', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all project expenses for a date range.
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return Collection
     */
    public function getExpensesForPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return ExpenseSchedule::where('is_project_expense', true)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with(['category', 'paidFromAccount'])
            ->orderByDesc('expense_date')
            ->get();
    }

    /**
     * Get summary of project expenses by project.
     *
     * @param Carbon|null $startDate Start date (optional)
     * @param Carbon|null $endDate End date (optional)
     * @return array
     */
    public function getExpensesByProject(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ExpenseSchedule::where('is_project_expense', true)
            ->with('category');

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        $expenses = $query->get();

        $byProject = $expenses->groupBy('project_id');

        $summary = [];
        foreach ($byProject as $projectId => $projectExpenses) {
            $project = Project::find($projectId);

            $summary[] = [
                'project_id' => $projectId,
                'project_name' => $project?->name ?? 'Unknown Project',
                'total_count' => $projectExpenses->count(),
                'total_amount' => $projectExpenses->sum('amount'),
                'paid_amount' => $projectExpenses->where('payment_status', 'paid')->sum('paid_amount'),
                'pending_amount' => $projectExpenses->where('payment_status', 'pending')->sum('amount'),
            ];
        }

        return $summary;
    }

    /**
     * Bulk sync all unsynced project costs.
     *
     * @return array Sync summary
     */
    public function bulkSyncAllProjects(): array
    {
        $unsyncedCosts = ProjectCost::where('synced_to_accounting', false)
            ->with('project')
            ->get();

        if ($unsyncedCosts->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No unsynced costs found across all projects',
                'synced' => 0,
                'total_amount' => 0,
            ];
        }

        $synced = 0;
        $failed = 0;
        $totalAmount = 0;

        foreach ($unsyncedCosts as $cost) {
            $result = $this->syncCost($cost);

            if ($result['success']) {
                $synced++;
                $totalAmount += $cost->amount;
            } else {
                $failed++;
            }
        }

        Log::info('Bulk project cost sync completed', [
            'synced' => $synced,
            'failed' => $failed,
            'total_amount' => $totalAmount,
        ]);

        return [
            'success' => $failed === 0,
            'message' => "Synced {$synced} costs" . ($failed > 0 ? ", {$failed} failed" : ''),
            'synced' => $synced,
            'failed' => $failed,
            'total_amount' => $totalAmount,
        ];
    }
}
