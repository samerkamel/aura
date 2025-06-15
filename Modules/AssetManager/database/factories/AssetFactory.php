<?php

namespace Modules\AssetManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AssetManager\Models\Asset;

/**
 * Asset Factory
 *
 * Factory for creating Asset model instances for testing.
 *
 * @author Dev Agent
 */
class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $types = ['Laptop', 'Desktop', 'Phone', 'Tablet', 'Monitor', 'Keyboard', 'Mouse'];

        return [
            'name' => $this->faker->company . ' ' . $this->faker->randomElement($types),
            'type' => $this->faker->randomElement($types),
            'serial_number' => strtoupper($this->faker->bothify('??###??###')),
            'purchase_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'purchase_price' => $this->faker->randomFloat(2, 100, 5000),
            'description' => $this->faker->sentence(10),
            'status' => $this->faker->randomElement(['available', 'assigned', 'maintenance', 'retired']),
        ];
    }

    /**
     * Indicate that the asset is available.
     */
    public function available(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'available',
        ]);
    }

    /**
     * Indicate that the asset is assigned.
     */
    public function assigned(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'assigned',
        ]);
    }

    /**
     * Indicate that the asset is in maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'maintenance',
        ]);
    }

    /**
     * Indicate that the asset is retired.
     */
    public function retired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'retired',
        ]);
    }
}
