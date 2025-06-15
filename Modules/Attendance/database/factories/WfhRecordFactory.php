<?php

namespace Modules\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attendance\Models\WfhRecord;
use Modules\HR\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

/**
 * WFH Record Factory
 *
 * Factory for creating test WFH records.
 *
 * @author Dev Agent
 */
class WfhRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = WfhRecord::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Set the WFH date to a specific date.
     */
    public function onDate(Carbon $date): static
    {
        return $this->state(fn(array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Set the WFH date to today.
     */
    public function today(): static
    {
        return $this->state(fn(array $attributes) => [
            'date' => Carbon::today(),
        ]);
    }

    /**
     * Set the WFH date to a future date.
     */
    public function future(): static
    {
        return $this->state(fn(array $attributes) => [
            'date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }
}
