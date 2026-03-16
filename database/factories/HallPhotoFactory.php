<?php

namespace Database\Factories;
use App\Models\Hall;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HallPhoto>
 */
class HallPhotoFactory extends Factory
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
            // Генерируем случайную ссылку на изображение комнаты
            'url' => 'https://loremflickr.com/800/600/room?lock=' . fake()->unique()->numberBetween(1, 1000),
        ];
    }
}
