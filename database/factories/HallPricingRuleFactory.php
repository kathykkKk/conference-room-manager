<?php

namespace Database\Factories;
use App\Models\Hall;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HallPricingRule>
 */
class HallPricingRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       return [
            'hall_id' => Hall::factory(),
            'priority' => fake()->numberBetween(0, 10),
            'price_per_hour' => fake()->randomFloat(2, 500, 5000),
            'apply_from_date' => now()->toDateString(),
            'apply_until_date' => fake()->optional()->dateTimeBetween('+1 month', '+6 months')?->format('Y-m-d'),
            // Формат дней недели: "1,2,3,4,5"
            'weekdays' => implode(',', fake()->randomElements(['1', '2', '3', '4', '5', '6', '7'], rand(1, 7))),
            'time_from' => '08:00:00',
            'time_to' => '22:00:00',
        ];
    }
}
