<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Booking;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'booking_id' => Booking::factory(),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
        ];
    }
}
