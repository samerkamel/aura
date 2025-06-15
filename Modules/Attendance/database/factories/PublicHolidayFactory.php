<?php

namespace Modules\Attendance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attendance\Models\PublicHoliday;

/**
 * PublicHolidayFactory
 *
 * Factory for creating PublicHoliday model instances for testing
 *
 * @author Dev Agent
 */
class PublicHolidayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PublicHoliday::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'New Year\'s Day',
                'Independence Day',
                'Christmas Day',
                'Labor Day',
                'National Day',
                'Victory Day',
                'Unity Day'
            ]),
            'date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d')
        ];
    }
}
