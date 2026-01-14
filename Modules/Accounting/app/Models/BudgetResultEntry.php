<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetResultEntry Model
 *
 * Consolidates budget values from Growth, Capacity, and Collection methods.
 * User selects the final budget value from among these three methods or enters custom value.
 */
class BudgetResultEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'product_id',
        'growth_value',
        'capacity_value',
        'collection_value',
        'average_value',
        'final_value',
    ];

    protected $casts = [
        'growth_value' => 'decimal:2',
        'capacity_value' => 'decimal:2',
        'collection_value' => 'decimal:2',
        'average_value' => 'decimal:2',
        'final_value' => 'decimal:2',
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
     * Calculate average of the three budget methods
     */
    public function calculateAverage(): float
    {
        $values = array_filter([
            $this->growth_value,
            $this->capacity_value,
            $this->collection_value,
        ], fn($val) => $val !== null);

        if (count($values) === 0) {
            return 0;
        }

        return array_sum($values) / count($values);
    }

    /**
     * Get all available budget values for comparison
     */
    public function getBudgetValues(): array
    {
        return [
            'growth' => $this->growth_value,
            'capacity' => $this->capacity_value,
            'collection' => $this->collection_value,
            'average' => $this->average_value,
        ];
    }

    /**
     * Get the method that has the highest budget value
     */
    public function getHighestMethod(): ?string
    {
        $values = $this->getBudgetValues();
        unset($values['average']); // Exclude average from comparison

        if (empty($values)) {
            return null;
        }

        $maxValue = max($values);
        return array_search($maxValue, $values);
    }

    /**
     * Get the method that has the lowest budget value
     */
    public function getLowestMethod(): ?string
    {
        $values = $this->getBudgetValues();
        unset($values['average']); // Exclude average from comparison

        if (empty($values)) {
            return null;
        }

        $minValue = min($values);
        return array_search($minValue, $values);
    }

    /**
     * Check if final value matches one of the three methods
     */
    public function isFinalFromMethod(): ?string
    {
        if (!$this->final_value) {
            return null;
        }

        if ($this->final_value == $this->growth_value) {
            return 'growth';
        }

        if ($this->final_value == $this->capacity_value) {
            return 'capacity';
        }

        if ($this->final_value == $this->collection_value) {
            return 'collection';
        }

        if ($this->final_value == $this->average_value) {
            return 'average';
        }

        return 'custom';
    }

    /**
     * Get percentage difference between two methods
     */
    public function getPercentageDifference(string $method1, string $method2): float
    {
        $val1 = $this->{$method1 . '_value'};
        $val2 = $this->{$method2 . '_value'};

        if ($val1 == 0 && $val2 == 0) {
            return 0;
        }

        if ($val2 == 0) {
            return 100;
        }

        return (($val1 - $val2) / $val2) * 100;
    }
}
