<?php

namespace Modules\Payroll\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payroll\Models\BillableHour;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

/**
 * BillableHour Factory
 *
 * Factory for creating test billable hour records.
 *
 * @author Dev Agent
 */
class BillableHourFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BillableHour::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'payroll_period_start_date' => Carbon::now()->startOfMonth(),
            'hours' => $this->faker->randomFloat(2, 0, 999.99),
        ];
    }

    /**
     * Indicate that the billable hours are for current period.
     */
    public function currentPeriod(): static
    {
        return $this->state(fn(array $attributes) => [
            'payroll_period_start_date' => Carbon::now()->startOfMonth(),
        ]);
    }

    /**
     * Indicate that the billable hours are for a specific period.
     */
    public function forPeriod(Carbon $period): static
    {
        return $this->state(fn(array $attributes) => [
            'payroll_period_start_date' => $period->startOfMonth(),
        ]);
    }

    /**
     * Set specific hours amount.
     */
    public function withHours(float $hours): static
    {
        return $this->state(fn(array $attributes) => [
            'hours' => $hours,
        ]);
    }
}
