<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BudgetCollectionPattern Model
 *
 * Represents a payment distribution pattern showing percentage paid in each month.
 * Example: 60% paid in month 1, 40% in month 2.
 */
class BudgetCollectionPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_collection_entry_id',
        'pattern_name',
        'contract_percentage',
        'month_1_pct',
        'month_2_pct',
        'month_3_pct',
        'month_4_pct',
        'month_5_pct',
        'month_6_pct',
        'month_7_pct',
        'month_8_pct',
        'month_9_pct',
        'month_10_pct',
        'month_11_pct',
        'month_12_pct',
    ];

    protected $casts = [
        'contract_percentage' => 'decimal:2',
        'month_1_pct' => 'decimal:2',
        'month_2_pct' => 'decimal:2',
        'month_3_pct' => 'decimal:2',
        'month_4_pct' => 'decimal:2',
        'month_5_pct' => 'decimal:2',
        'month_6_pct' => 'decimal:2',
        'month_7_pct' => 'decimal:2',
        'month_8_pct' => 'decimal:2',
        'month_9_pct' => 'decimal:2',
        'month_10_pct' => 'decimal:2',
        'month_11_pct' => 'decimal:2',
        'month_12_pct' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    /**
     * Get the collection entry this pattern belongs to
     */
    public function collectionEntry(): BelongsTo
    {
        return $this->belongsTo(BudgetCollectionEntry::class);
    }

    // ==================== Methods ====================

    /**
     * Get all monthly percentages as array
     */
    public function getMonthlyPercentages(): array
    {
        $percentages = [];
        for ($month = 1; $month <= 12; $month++) {
            $column = "month_{$month}_pct";
            $percentages[$month] = $this->{$column};
        }
        return $percentages;
    }

    /**
     * Set monthly percentages from array
     */
    public function setMonthlyPercentages(array $percentages): void
    {
        for ($month = 1; $month <= 12; $month++) {
            $column = "month_{$month}_pct";
            $this->{$column} = $percentages[$month] ?? 0;
        }
    }

    /**
     * Calculate collection months for this pattern
     *
     * Example: If 60% paid in month 1 and 40% in month 2:
     * = (1 * 0.60) + (2 * 0.40) = 0.60 + 0.80 = 1.40 months
     */
    public function calculateCollectionMonths(): float
    {
        $totalMonths = 0;
        $percentages = $this->getMonthlyPercentages();

        foreach ($percentages as $month => $percentage) {
            if ($percentage > 0) {
                $totalMonths += $month * ($percentage / 100);
            }
        }

        return $totalMonths;
    }

    /**
     * Validate that monthly percentages sum to 100
     */
    public function isValid(): bool
    {
        $total = collect($this->getMonthlyPercentages())->sum();
        return abs($total - 100) < 0.01; // Allow for rounding errors
    }

    /**
     * Get validation error message if invalid
     */
    public function getValidationError(): ?string
    {
        $total = collect($this->getMonthlyPercentages())->sum();

        if ($total == 0) {
            return 'Monthly percentages must sum to 100%';
        }

        if ($total != 100) {
            return "Monthly percentages sum to {$total}%, must be 100%";
        }

        return null;
    }

    /**
     * Get pattern description for display
     */
    public function getDescription(): string
    {
        $parts = [];
        $percentages = $this->getMonthlyPercentages();

        for ($month = 1; $month <= 12; $month++) {
            if ($percentages[$month] > 0) {
                $monthName = $this->getMonthName($month);
                $parts[] = "{$percentages[$month]}% in {$monthName}";
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Get month name from number
     */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];

        return $months[$month] ?? 'N/A';
    }
}
