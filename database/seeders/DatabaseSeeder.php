<?php

namespace Database\Seeders;

use App\Models\BusinessPark;
use App\Models\Hall;
use App\Models\Equipment;
use App\Models\HallPhoto;
use App\Models\Client;
use App\Models\Manager;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\HallPricingRule;
use App\Models\Report;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $equipmentItems = [
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
        $equipment = collect();
        foreach ($equipmentItems as $item) {
            $equipment->push(Equipment::create([
                'name' => $item[0],
                'category' => $item[1],
            ]));
        }

        $cities = ['Москва', 'Санкт-Петербург', 'Казань', 'Новосибирск', 'Екатеринбург'];
        $clients = Client::factory(15)->create();

        BusinessPark::factory(5)->create()->each(function ($park, $index) use ($equipment, $clients, $cities) {
            $park->update(['city' => $cities[$index % count($cities)]]);
            
            Manager::factory()->create(['business_park_id' => $park->id]);

            // 4. Залы
            $prices = [800, 1200, 1500, 2000, 2500, 3000];
            Hall::factory(4)->create(['business_park_id' => $park->id])->each(function ($hall) use ($equipment, $clients, $prices) {
                $pricePerHour = $prices[array_rand($prices)];
                HallPricingRule::create([
                    'hall_id' => $hall->id,
                    'weekdays' => '1,2,3,4,5,6,7',
                    'time_from' => '00:00:00',
                    'time_to' => '23:59:59',
                    'priority' => 100,
                    'price_per_hour' => $pricePerHour,
                    'apply_from_date' => Carbon::now()->toDateString(),
                ]);

                HallPhoto::factory(2)->create(['hall_id' => $hall->id]);

                $randomItems = $equipment->random(min(4, $equipment->count()));
                foreach ($randomItems as $item) {
                    $hall->equipment()->attach($item->id, [
                        'id' => Str::uuid(),
                        'quantity' => rand(1, 5),
                    ]);
                }

                $today = Carbon::now()->startOfDay();
                $slots = [
                    ['09:00', '11:00'],
                    ['14:00', '16:00'],
                    ['18:00', '20:00'],
                ];

                $bookingCount = rand(1, 3);
                for ($i = 0; $i < $bookingCount; $i++) {
                    $day = $today->copy()->addDays(rand(0, 21));
                    $slot = $slots[$i % count($slots)];
                    $start = $day->copy()->setTimeFromTimeString($slot[0]);
                    $end = $day->copy()->setTimeFromTimeString($slot[1]);
                    $hours = $start->diffInMinutes($end) / 60;
                    $status = ['pending', 'confirmed', 'completed'][rand(0, 2)];
                    $booking = Booking::create([
                        'hall_id' => $hall->id,
                        'client_id' => $clients->random()->id,
                        'start_datetime' => $start,
                        'end_datetime' => $end,
                        'total_price' => round($hours * $pricePerHour, 2),
                        'status' => $status,
                    ]);
                    if (in_array($status, ['confirmed', 'completed'])) {
                        Payment::factory()->create([
                            'booking_id' => $booking->id,
                            'amount' => $booking->total_price,
                            'payment_status' => 'paid',
                        ]);
                    }
                }
            });
        });

        $firstManager = Manager::first();
        if ($firstManager) {
            Report::factory(3)->create([
                'manager_id' => $firstManager->id,
                'report_type' => 'financial'
            ]);
        }
    }
}