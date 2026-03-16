<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Payment;
use App\Models\Manager;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'amount' => fake()->randomFloat(2, 100, 1000), // Обычно меньше или равно платежу
            'reason' => fake()->randomElement(['Client cancelled', 'Double payment', 'Technical issues']),
            'status' => fake()->randomElement(['pending', 'processed', 'rejected']),
            'processed_by_manager_id' => Manager::factory(),
            'processed_at' => fake()->optional()->dateTimeThisMonth(),
        ];
    }
}
