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
        $items = [
            ['Проектор', 'Видео'],
            ['Флипчарт', 'Мебель'],
            ['Кофе-машина', 'Сервис'],
            ['Микрофон', 'Аудио'],
            ['Колонки', 'Аудио'],
            ['Экран', 'Видео'],
            ['Доска маркерная', 'Мебель'],
            ['Видеокамера', 'Видео'],
            ['Принтер', 'Офис'],
            ['Кондиционер', 'Климат'],
        ];
        $item = fake()->randomElement($items);
        return [
            'name' => $item[0],
            'category' => $item[1],
        ];
    }
}
