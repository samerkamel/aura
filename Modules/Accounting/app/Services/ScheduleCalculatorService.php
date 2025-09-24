<?php

namespace Modules\Accounting\Services;

/**
 * ScheduleCalculatorService
 *
 * Provides calculation utilities for expense schedules.
 * Simplified version that only handles expense schedule calculations.
 */
class ScheduleCalculatorService
{
    /**
     * Get available frequency options for expense schedules.
     */
    public function getFrequencyOptions(): array
    {
        return [
            'weekly' => 'Weekly',
            'bi-weekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
    }

    /**
     * Calculate monthly equivalent amount for expense schedules.
     */
    public function calculateMonthlyEquivalent(string $frequencyType, int $frequencyValue, float $amount): float
    {
        $multiplier = match($frequencyType) {
            'weekly' => 4.33 / $frequencyValue,
            'bi-weekly' => 2.17 / $frequencyValue,
            'monthly' => 1 / $frequencyValue,
            'quarterly' => 1 / ($frequencyValue * 3),
            'yearly' => 1 / ($frequencyValue * 12),
            default => 1,
        };

        return $amount * $multiplier;
    }
}