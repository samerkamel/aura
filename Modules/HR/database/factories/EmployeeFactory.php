<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Employee;

/**
 * Employee Factory
 *
 * Factory for generating test Employee model instances.
 *
 * @author Dev Agent
 */
class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'position' => $this->faker->jobTitle(),
            'start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'base_salary' => $this->faker->randomFloat(2, 30000, 150000),
            'status' => 'active',
            'contact_info' => [
                'phone' => $this->faker->phoneNumber(),
                'address' => $this->faker->address(),
            ],
            'bank_info' => [
                'bank_name' => $this->faker->company().' Bank',
                'account_number' => $this->faker->bankAccountNumber(),
            ],
        ];
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'terminated',
            'termination_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the employee has resigned.
     */
    public function resigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resigned',
            'termination_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }
}
