<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetCapacityEntry;
use Modules\Accounting\Models\BudgetCapacityHire;
use App\Models\Product;
use Modules\HR\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * CapacityService
 *
 * Handles capacity-based budget calculations using employee headcount and billable hours.
 * Calculates weighted headcount accounting for new hires during the year.
 */
class CapacityService
{
    /**
     * Create a capacity entry for a product in a budget
     */
    public function createCapacityEntry(Budget $budget, Product $product): BudgetCapacityEntry
    {
        return BudgetCapacityEntry::create([
            'budget_id' => $budget->id,
            'product_id' => $product->id,
            'last_year_headcount' => 0,
            'last_year_available_hours' => 0,
            'last_year_avg_hourly_price' => 0,
            'last_year_income' => 0,
            'last_year_billable_hours' => 0,
            'last_year_billable_pct' => 0,
            'next_year_headcount' => 0,
            'next_year_avg_hourly_price' => 0,
            'next_year_billable_pct' => 0,
        ]);
    }

    /**
     * Add a planned hire to a capacity entry
     */
    public function addHire(BudgetCapacityEntry $entry, int $hireMonth, int $hireCount = 1): BudgetCapacityHire
    {
        return BudgetCapacityHire::create([
            'budget_capacity_entry_id' => $entry->id,
            'hire_month' => $hireMonth,
            'hire_count' => $hireCount,
        ]);
    }

    /**
     * Update hire information
     */
    public function updateHire(BudgetCapacityHire $hire, int $hireMonth, int $hireCount): void
    {
        $hire->update([
            'hire_month' => $hireMonth,
            'hire_count' => $hireCount,
        ]);
    }

    /**
     * Delete a hire
     */
    public function deleteHire(BudgetCapacityHire $hire): void
    {
        $hire->delete();
    }

    /**
     * Update last year data for a capacity entry
     */
    public function updateLastYearData(
        BudgetCapacityEntry $entry,
        int $headcount,
        float $availableHours,
        float $avgHourlyPrice,
        float $income,
        float $billableHours,
        float $billablePct
    ): void {
        $entry->update([
            'last_year_headcount' => $headcount,
            'last_year_available_hours' => $availableHours,
            'last_year_avg_hourly_price' => $avgHourlyPrice,
            'last_year_income' => $income,
            'last_year_billable_hours' => $billableHours,
            'last_year_billable_pct' => $billablePct,
        ]);
    }

    /**
     * Update next year planning data
     */
    public function updateNextYearData(
        BudgetCapacityEntry $entry,
        int $headcount,
        float $avgHourlyPrice,
        float $billablePct
    ): void {
        $entry->update([
            'next_year_headcount' => $headcount,
            'next_year_avg_hourly_price' => $avgHourlyPrice,
            'next_year_billable_pct' => $billablePct,
        ]);

        // Auto-calculate budgeted income
        $this->calculateAndSaveBudgetedIncome($entry);
    }

    /**
     * Calculate weighted headcount accounting for new hires
     *
     * Example: Base 5 + hire 2 in June = 5 + (2 * 7/12) = 5.83
     */
    public function calculateWeightedHeadcount(BudgetCapacityEntry $entry): float
    {
        return $entry->calculateWeightedHeadcount();
    }

    /**
     * Calculate budgeted income using capacity method
     *
     * Formula: Available Hours × Weighted Headcount × Avg Hourly Price × Billable %
     */
    public function calculateBudgetedIncome(BudgetCapacityEntry $entry): float
    {
        return $entry->calculateBudgetedIncome();
    }

    /**
     * Calculate and save budgeted income
     */
    public function calculateAndSaveBudgetedIncome(BudgetCapacityEntry $entry): void
    {
        $budgetedIncome = $this->calculateBudgetedIncome($entry);
        $entry->update(['budgeted_income' => $budgetedIncome]);
    }

    /**
     * Get capacity entries for a budget with hires
     */
    public function getBudgetCapacityEntries(Budget $budget)
    {
        return $budget->capacityEntries()
            ->with('product', 'hires')
            ->get();
    }

    /**
     * Get total new hires for a capacity entry
     */
    public function getTotalNewHires(BudgetCapacityEntry $entry): int
    {
        return $entry->getTotalNewHires();
    }

    /**
     * Get hires by month for display
     */
    public function getHiresByMonth(BudgetCapacityEntry $entry): array
    {
        return $entry->getHiresByMonth();
    }

    /**
     * Populate capacity entry from last year actual data
     * This would integrate with actual invoicing/payroll data
     */
    public function populateFromLastYear(BudgetCapacityEntry $entry, Budget $previousBudget = null): void
    {
        // Implementation would fetch actual last year data from invoices and payroll
        // For now, this is a placeholder for the structure
    }

    /**
     * Populate capacity entries from active employee data.
     * Maps employee teams to products and calculates headcount and average hourly rates.
     *
     * @param Budget $budget The budget to populate
     * @return array Summary of populated data per product
     */
    public function populateFromEmployees(Budget $budget): array
    {
        // Get employee statistics grouped by team
        $employeeStats = Employee::where('status', 'active')
            ->whereNotNull('team')
            ->select('team')
            ->selectRaw('COUNT(*) as headcount')
            ->selectRaw('AVG(hourly_rate) as avg_hourly_rate')
            ->groupBy('team')
            ->get()
            ->keyBy('team');

        $results = [];

        // Get all capacity entries for this budget
        $capacityEntries = $budget->capacityEntries()->with('product')->get();

        foreach ($capacityEntries as $entry) {
            $productName = $entry->product->name;

            // Check if we have employee data for this product/team
            if ($employeeStats->has($productName)) {
                $stats = $employeeStats[$productName];

                // Update the capacity entry with employee data
                $entry->update([
                    'next_year_headcount' => $stats->headcount,
                    'next_year_avg_hourly_price' => $stats->avg_hourly_rate ?? 0,
                ]);

                $results[$productName] = [
                    'updated' => true,
                    'headcount' => $stats->headcount,
                    'avg_hourly_rate' => $stats->avg_hourly_rate,
                ];
            } else {
                $results[$productName] = [
                    'updated' => false,
                    'headcount' => 0,
                    'avg_hourly_rate' => 0,
                    'message' => 'No employees assigned to this team',
                ];
            }
        }

        return $results;
    }

    /**
     * Get employee statistics for display purposes.
     * Shows headcount and average hourly rate per team.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEmployeeStatsByTeam(): \Illuminate\Support\Collection
    {
        return Employee::where('status', 'active')
            ->whereNotNull('team')
            ->select('team')
            ->selectRaw('COUNT(*) as headcount')
            ->selectRaw('AVG(hourly_rate) as avg_hourly_rate')
            ->selectRaw('MIN(hourly_rate) as min_hourly_rate')
            ->selectRaw('MAX(hourly_rate) as max_hourly_rate')
            ->groupBy('team')
            ->get();
    }
}
