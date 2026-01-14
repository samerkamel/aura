<?php

namespace Modules\Accounting\Services\Budget;

use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetGrowthEntry;
use App\Models\Product;

/**
 * GrowthService
 *
 * Handles growth-based budget projections using trendline analysis.
 * Supports linear, logarithmic, and polynomial trendline types.
 */
class GrowthService
{
    /**
     * Create a growth entry for a product in a budget
     */
    public function createGrowthEntry(Budget $budget, Product $product): BudgetGrowthEntry
    {
        return BudgetGrowthEntry::create([
            'budget_id' => $budget->id,
            'product_id' => $product->id,
            'trendline_type' => 'linear',
            'polynomial_order' => null,
        ]);
    }

    /**
     * Calculate trendline projection for historical data
     *
     * @param array $historicalData [year_minus_3, year_minus_2, year_minus_1]
     * @param string $type 'linear', 'logarithmic', or 'polynomial'
     * @param int|null $polynomialOrder Order for polynomial (default 2)
     * @return float Projected value for current year
     */
    public function calculateTrendlineProjection(
        array $historicalData,
        string $type = 'linear',
        ?int $polynomialOrder = null
    ): float {
        // Filter out null values and create x-axis points
        $validData = array_filter($historicalData, fn($val) => $val !== null);

        if (count($validData) < 2) {
            return end($historicalData) ?? 0;
        }

        $x = [];
        $y = [];
        $pointIndex = 0;

        // Build x,y arrays (x = 1, 2, 3 for 3-year data)
        foreach ($historicalData as $value) {
            if ($value !== null) {
                $x[] = count($x) + 1;
                $y[] = $value;
            }
        }

        return match($type) {
            'linear' => $this->calculateLinearTrendline($x, $y),
            'logarithmic' => $this->calculateLogarithmicTrendline($x, $y),
            'polynomial' => $this->calculatePolynomialTrendline($x, $y, $polynomialOrder ?? 2),
            default => end($y) ?? 0,
        };
    }

    /**
     * Calculate linear trendline projection
     * Formula: y = mx + b
     */
    private function calculateLinearTrendline(array $x, array $y): float
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($xi, $yi) => $xi * $yi, $x, $y));
        $sumX2 = array_sum(array_map(fn($xi) => $xi * $xi, $x));

        $m = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $b = ($sumY - $m * $sumX) / $n;

        // Project to next point (x = n + 1)
        return $m * ($n + 1) + $b;
    }

    /**
     * Calculate logarithmic trendline projection
     * Formula: y = a * ln(x) + b
     */
    private function calculateLogarithmicTrendline(array $x, array $y): float
    {
        $n = count($x);
        $sumY = array_sum($y);
        $lnX = array_map(fn($xi) => log($xi), $x);
        $sumLnX = array_sum($lnX);
        $sumLnX2 = array_sum(array_map(fn($ln) => $ln * $ln, $lnX));
        $sumYLnX = array_sum(array_map(fn($yi, $ln) => $yi * $ln, $y, $lnX));

        $denominator = $n * $sumLnX2 - $sumLnX * $sumLnX;

        if ($denominator == 0) {
            return end($y) ?? 0;
        }

        $a = ($n * $sumYLnX - $sumLnX * $sumY) / $denominator;
        $b = ($sumY - $a * $sumLnX) / $n;

        // Project to next point
        return $a * log($n + 1) + $b;
    }

    /**
     * Calculate polynomial trendline projection
     * Uses least squares fit for polynomial of given order
     */
    private function calculatePolynomialTrendline(array $x, array $y, int $order = 2): float
    {
        $n = count($x);

        // Build the system of equations for polynomial fit
        // We'll use a simplified approach for orders 2-3
        if ($order == 2) {
            return $this->fitQuadratic($x, $y);
        } elseif ($order == 3) {
            return $this->fitCubic($x, $y);
        }

        // Default to linear for higher orders
        return $this->calculateLinearTrendline($x, $y);
    }

    /**
     * Fit quadratic (order 2) polynomial: y = ax² + bx + c
     */
    private function fitQuadratic(array $x, array $y): float
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumX2 = array_sum(array_map(fn($xi) => $xi * $xi, $x));
        $sumX3 = array_sum(array_map(fn($xi) => $xi * $xi * $xi, $x));
        $sumX4 = array_sum(array_map(fn($xi) => $xi * $xi * $xi * $xi, $x));
        $sumXY = array_sum(array_map(fn($xi, $yi) => $xi * $yi, $x, $y));
        $sumX2Y = array_sum(array_map(fn($xi, $yi) => $xi * $xi * $yi, $x, $y));

        // Solve using Cramer's rule or Gaussian elimination
        // Simplified: use linear approximation if singular
        try {
            $det = $n * ($sumX2 * $sumX4 - $sumX3 * $sumX3) - $sumX * ($sumX * $sumX4 - $sumX2 * $sumX3) + $sumX2 * ($sumX * $sumX3 - $sumX2 * $sumX2);

            if (abs($det) < 1e-10) {
                return $this->calculateLinearTrendline($x, $y);
            }

            // Calculate coefficients (simplified)
            $c = ($sumY * ($sumX2 * $sumX4 - $sumX3 * $sumX3) - $sumX * ($sumXY * $sumX4 - $sumX2Y * $sumX3) + $sumX2 * ($sumXY * $sumX3 - $sumX2Y * $sumX2)) / $det;
            $b = ($n * ($sumXY * $sumX4 - $sumX2Y * $sumX3) - $sumY * ($sumX * $sumX4 - $sumX2 * $sumX3) + $sumX2 * ($sumX * $sumX2Y - $sumXY * $sumX2)) / $det;
            $a = ($n * ($sumX2 * $sumX2Y - $sumXY * $sumX3) - $sumX * ($sumX * $sumX2Y - $sumXY * $sumX2) + $sumY * ($sumX * $sumX3 - $sumX2 * $sumX2)) / $det;

            $nextX = $n + 1;
            return $a * $nextX * $nextX + $b * $nextX + $c;
        } catch (\Exception $e) {
            return $this->calculateLinearTrendline($x, $y);
        }
    }

    /**
     * Fit cubic (order 3) polynomial: y = ax³ + bx² + cx + d
     * Falls back to quadratic for simplicity
     */
    private function fitCubic(array $x, array $y): float
    {
        // For simplicity, use quadratic as approximation
        return $this->fitQuadratic($x, $y);
    }

    /**
     * Update growth entry with calculated budgeted value
     */
    public function updateBudgetedValue(BudgetGrowthEntry $entry, float $value): void
    {
        $entry->update(['budgeted_value' => $value]);
    }

    /**
     * Calculate growth method income for a product
     */
    public function calculateGrowthIncome(BudgetGrowthEntry $entry): float
    {
        if (!$entry->budgeted_value) {
            return 0;
        }

        return $entry->budgeted_value;
    }

    /**
     * Get all growth entries for a budget
     */
    public function getBudgetGrowthEntries(Budget $budget)
    {
        return $budget->growthEntries()->with('product')->get();
    }

    /**
     * Populate historical data from contracts/invoices for all growth entries
     *
     * Calculates income by product for the 3 years prior to the budget year
     * using paid contract payments allocated to each product.
     */
    public function populateHistoricalData(Budget $budget): array
    {
        $budgetYear = $budget->year;
        $results = [];

        // Eager load growth entries with product relationship
        $entries = $budget->growthEntries()->with('product')->get();

        foreach ($entries as $entry) {
            $productId = $entry->product_id;

            if (!$productId) {
                continue;
            }

            try {
                // Get income for each historical year
                $yearMinus3 = $this->getProductIncomeForYear($productId, $budgetYear - 3);
                $yearMinus2 = $this->getProductIncomeForYear($productId, $budgetYear - 2);
                $yearMinus1 = $this->getProductIncomeForYear($productId, $budgetYear - 1);

                // Update the entry
                $entry->update([
                    'year_minus_3' => $yearMinus3,
                    'year_minus_2' => $yearMinus2,
                    'year_minus_1' => $yearMinus1,
                ]);

                $results[$entry->id] = [
                    'product_id' => $productId,
                    'product_name' => $entry->product->name ?? 'Unknown',
                    'year_minus_3' => $yearMinus3,
                    'year_minus_2' => $yearMinus2,
                    'year_minus_1' => $yearMinus1,
                ];
            } catch (\Exception $e) {
                \Log::error("Error populating historical data for product {$productId}: " . $e->getMessage());
                $results[$entry->id] = [
                    'product_id' => $productId,
                    'product_name' => $entry->product->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get total income for a product in a specific year
     *
     * Uses paid contract payments where the contract has the product allocated.
     * Income is prorated based on the product's allocation percentage.
     */
    public function getProductIncomeForYear(int $productId, int $year): float
    {
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        // Get all contracts that have this product allocated
        $contracts = \Modules\Accounting\Models\Contract::whereHas('products', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })->get();

        $totalIncome = 0;

        foreach ($contracts as $contract) {
            // Get the product allocation for this contract
            $allocation = $contract->products()
                ->where('product_id', $productId)
                ->first();

            if (!$allocation) {
                continue;
            }

            // Get paid payments for this contract in the target year
            $paidAmount = $contract->payments()
                ->where('status', 'paid')
                ->whereYear('paid_date', $year)
                ->sum('amount');

            if ($paidAmount > 0) {
                // Apply allocation percentage or use proportion of contract total
                $allocationType = $allocation->pivot->allocation_type ?? 'percentage';
                $allocationPct = $allocation->pivot->allocation_percentage ?? 0;
                $allocationAmt = $allocation->pivot->allocation_amount ?? 0;

                if ($allocationType === 'percentage' && $allocationPct > 0) {
                    $totalIncome += $paidAmount * ($allocationPct / 100);
                } elseif ($allocationType === 'amount' && $allocationAmt > 0 && $contract->total_amount > 0) {
                    // Calculate proportion based on allocation amount vs contract total
                    $proportion = $allocationAmt / $contract->total_amount;
                    $totalIncome += $paidAmount * $proportion;
                } else {
                    // No allocation info - assume equal distribution among products
                    $productCount = $contract->products()->count();
                    if ($productCount > 0) {
                        $totalIncome += $paidAmount / $productCount;
                    }
                }
            }
        }

        return round($totalIncome, 2);
    }
}
