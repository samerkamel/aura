<?php

namespace Modules\Leave\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leave\Models\LeavePolicyTier;
use Modules\Leave\Models\LeavePolicy;

/**
 * Leave Policy Tier Factory
 *
 * Factory for creating test leave policy tiers.
 *
 * @author Dev Agent
 */
class LeavePolicyTierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = LeavePolicyTier::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'leave_policy_id' => LeavePolicy::factory(),
            'min_years' => $this->faker->numberBetween(0, 5),
            'max_years' => $this->faker->optional()->numberBetween(5, 20),
            'annual_days' => $this->faker->numberBetween(10, 30),
            'monthly_accrual_rate' => $this->faker->randomFloat(2, 0.83, 2.5),
        ];
    }

    /**
     * Create a tier for employees with 0-2 years of service.
     */
    public function newEmployee(): static
    {
        return $this->state(fn(array $attributes) => [
            'min_years' => 0,
            'max_years' => 2,
            'annual_days' => 15,
            'monthly_accrual_rate' => 1.25,
        ]);
    }

    /**
     * Create a tier for employees with 3-5 years of service.
     */
    public function experienced(): static
    {
        return $this->state(fn(array $attributes) => [
            'min_years' => 3,
            'max_years' => 5,
            'annual_days' => 20,
            'monthly_accrual_rate' => 1.67,
        ]);
    }

    /**
     * Create a tier for employees with 6+ years of service.
     */
    public function senior(): static
    {
        return $this->state(fn(array $attributes) => [
            'min_years' => 6,
            'max_years' => null,
            'annual_days' => 25,
            'monthly_accrual_rate' => 2.08,
        ]);
    }
}
