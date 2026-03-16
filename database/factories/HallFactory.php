<?php

namespace Database\Factories;
use App\Models\BusinessPark;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hall>
 */
class HallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'business_park_id' => BusinessPark::factory(), // Создаст парк автоматически
            'name' => 'Зал ' . fake()->word(),
            'capacity' => fake()->numberBetween(10, 200),
            'area_sq_m' => fake()->randomFloat(2, 20, 500),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['available', 'unavailable']),
        ];
    }
}
