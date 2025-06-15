<?php

namespace Modules\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attendance\Models\AttendanceRule;

/**
 * AttendanceRule Factory
 *
 * Factory for creating test attendance rules.
 *
 * @author Dev Agent
 */
class AttendanceRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = AttendanceRule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'rule_name' => $this->faker->words(3, true),
            'rule_type' => $this->faker->randomElement(['flexible_hours', 'late_penalty', 'permission_override', 'wfh_policy']),
            'config' => [
                'enabled' => true,
                'parameters' => [],
            ],
        ];
    }

    /**
     * Create a WFH policy rule state.
     */
    public function wfhPolicy(): static
    {
        return $this->state(fn(array $attributes) => [
            'rule_name' => 'WFH Policy',
            'rule_type' => 'wfh_policy',
            'config' => [
                'monthly_allowance_days' => 5,
                'attendance_contribution_percentage' => 80,
                'enabled' => true,
            ],
        ]);
    }

    /**
     * Create a flexible hours rule state.
     */
    public function flexibleHours(): static
    {
        return $this->state(fn(array $attributes) => [
            'rule_name' => 'Flexible Hours',
            'rule_type' => 'flexible_hours',
            'config' => [
                'core_hours_start' => '10:00',
                'core_hours_end' => '15:00',
                'min_daily_hours' => 8,
                'enabled' => true,
            ],
        ]);
    }

    /**
     * Create a late penalty rule state.
     */
    public function latePenalty(): static
    {
        return $this->state(fn(array $attributes) => [
            'rule_name' => 'Late Penalty',
            'rule_type' => 'late_penalty',
            'config' => [
                'grace_period_minutes' => 15,
                'penalty_per_minute' => 0.5,
                'max_penalty_per_day' => 50,
                'enabled' => true,
            ],
        ]);
    }
}
