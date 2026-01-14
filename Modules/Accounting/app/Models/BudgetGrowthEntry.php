<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetGrowthEntry Model
 *
 * Stores historical income data and trendline configuration for growth-based budget projections.
 */
class BudgetGrowthEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'product_id',
        'year_minus_3',
        'year_minus_2',
        'year_minus_1',
        'trendline_type',
        'polynomial_order',
        'budgeted_value',
    ];

    protected $casts = [
        'year_minus_3' => 'decimal:2',
        'year_minus_2' => 'decimal:2',
        'year_minus_1' => 'decimal:2',
        'budgeted_value' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    /**
     * Get the budget this entry belongs to
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the product this entry is for
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    // ==================== Methods ====================

    /**
     * Get historical data as array for trendline calculation
     */
    public function getHistoricalData(): array
    {
        return array_filter([
            $this->year_minus_3,
            $this->year_minus_2,
            $this->year_minus_1,
        ], fn($val) => $val !== null);
    }

    /**
     * Get historical data with year labels for chart display
     */
    public function getHistoricalDataForChart(): array
    {
        $data = [];
        $budget = $this->budget;

        if ($this->year_minus_3) {
            $data['year_' . ($budget->year - 3)] = $this->year_minus_3;
        }
        if ($this->year_minus_2) {
            $data['year_' . ($budget->year - 2)] = $this->year_minus_2;
        }
        if ($this->year_minus_1) {
            $data['year_' . ($budget->year - 1)] = $this->year_minus_1;
        }
        if ($this->budgeted_value) {
            $data['year_' . $budget->year] = $this->budgeted_value;
        }

        return $data;
    }

    /**
     * Check if we have enough data for trendline analysis
     */
    public function hasEnoughDataForTrendline(): bool
    {
        $count = collect([$this->year_minus_3, $this->year_minus_2, $this->year_minus_1])
            ->filter(fn($val) => $val !== null)
            ->count();

        return $count >= 2; // Need at least 2 data points
    }

    /**
     * Get trendline type display name
     */
    public function getTrendlineTypeLabel(): string
    {
        return match($this->trendline_type) {
            'linear' => 'Linear',
            'logarithmic' => 'Logarithmic',
            'polynomial' => "Polynomial (Order {$this->polynomial_order})",
            default => 'Unknown',
        };
    }
}
