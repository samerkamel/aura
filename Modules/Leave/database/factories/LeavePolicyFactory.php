<?php

namespace Modules\Leave\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Leave\Models\LeavePolicy;

/**
 * Leave Policy Factory
 *
 * Factory for creating test leave policies.
 *
 * @author Dev Agent
 */
class LeavePolicyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = LeavePolicy::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Annual Leave',
                'Sick Leave',
                'Personal Time Off',
                'Vacation Days',
                'Bereavement Leave',
            ]),
            'type' => $this->faker->randomElement(['PTO', 'Sick', 'Personal', 'Bereavement']),
            'description' => $this->faker->optional()->sentence(),
            'initial_days' => $this->faker->numberBetween(10, 30),
            'config' => [
                'accrual_method' => 'yearly',
                'carry_forward_allowed' => $this->faker->boolean(),
                'max_carry_forward_days' => $this->faker->numberBetween(0, 10),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the policy is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the policy is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the policy type.
     */
    public function type(string $type): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => $type,
        ]);
    }
}
