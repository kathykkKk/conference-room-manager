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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Оборудование
        $equipment = Equipment::factory(10)->create();

        // 2. Парки
        BusinessPark::factory(5)->create()->each(function ($park) use ($equipment) {
            
            Manager::factory()->create(['business_park_id' => $park->id]);

            // 3. Залы
            Hall::factory(4)->create(['business_park_id' => $park->id])->each(function ($hall) use ($equipment) {
                
                     HallPricingRule::factory()->create([
                        'hall_id' => $hall->id,
                        'weekdays' => '1,2,3,4,5,6,7',
                        'time_from' => '00:00:00',
                        'time_to' => '23:59:59',
                        'priority' => 100, // Низкий приоритет
                        'price_per_hour' => 1000
                    ]);


                HallPhoto::factory(2)->create(['hall_id' => $hall->id]);

                // ИСПРАВЛЕННЫЙ БЛОК: Привязываем оборудование по одному
                $randomItems = $equipment->random(3);
                foreach ($randomItems as $item) {
                    $hall->equipment()->attach($item->id, [
                        'id' => Str::uuid(), 
                        'quantity' => rand(1, 5)
                    ]);
                }

                // 4. Бронирования
                Booking::factory(3)->create(['hall_id' => $hall->id])->each(function ($booking) {
                    Payment::factory()->create([
                        'booking_id' => $booking->id,
                        'payment_status' => 'paid'
                    ]);
                });
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