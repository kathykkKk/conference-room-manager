<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => fake()->randomElement(['Проектор', 'Флипчарт', 'Кофе-машина', 'Микрофон']),
            'category' => fake()->randomElement(['Аудио', 'Видео', 'Мебель', 'Сервис']),
        ];
    }
}
