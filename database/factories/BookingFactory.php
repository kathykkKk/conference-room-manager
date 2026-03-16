<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hall;
use App\Models\Client;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $end = (clone $start)->modify('+' . rand(1, 8) . ' hours');

        return [
            'hall_id' => Hall::factory(),
            'client_id' => Client::factory(),
            'start_datetime' => $start,
            'end_datetime' => $end,
            'total_price' => fake()->randomFloat(2, 1000, 50000),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
        ];
    }
}
