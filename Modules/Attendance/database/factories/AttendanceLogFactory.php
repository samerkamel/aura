<?php

namespace Modules\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attendance\Models\AttendanceLog;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

/**
 * Attendance Log Factory
 *
 * Factory for creating test attendance logs.
 *
 * @author Dev Agent
 */
class AttendanceLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = AttendanceLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'timestamp' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'type' => $this->faker->randomElement(['sign_in', 'sign_out']),
        ];
    }

    /**
     * Create a sign-in log.
     */
    public function signIn(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'sign_in',
        ]);
    }

    /**
     * Create a sign-out log.
     */
    public function signOut(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'sign_out',
        ]);
    }

    /**
     * Set a specific timestamp.
     */
    public function timestamp(Carbon $timestamp): static
    {
        return $this->state(fn(array $attributes) => [
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Set today's date with a specific time.
     */
    public function today(string $time): static
    {
        return $this->state(fn(array $attributes) => [
            'timestamp' => Carbon::today()->setTimeFromTimeString($time),
        ]);
    }
}
