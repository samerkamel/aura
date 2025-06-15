<?php

namespace Modules\Leave\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leave\Models\LeaveRecord;
use Modules\Leave\Models\LeavePolicy;
use Modules\HR\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

/**
 * Leave Record Factory
 *
 * Factory for creating test leave records.
 *
 * @author Dev Agent
 */
class LeaveRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = LeaveRecord::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(0, 5) . ' days');

        return [
            'employee_id' => Employee::factory(),
            'leave_policy_id' => LeavePolicy::factory(),
            'start_date' => Carbon::instance($startDate),
            'end_date' => Carbon::instance($endDate),
            'status' => $this->faker->randomElement([
                LeaveRecord::STATUS_APPROVED,
                LeaveRecord::STATUS_PENDING,
                LeaveRecord::STATUS_REJECTED,
            ]),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the leave record is approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LeaveRecord::STATUS_APPROVED,
        ]);
    }

    /**
     * Indicate that the leave record is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LeaveRecord::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the leave record is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => LeaveRecord::STATUS_REJECTED,
        ]);
    }
}
