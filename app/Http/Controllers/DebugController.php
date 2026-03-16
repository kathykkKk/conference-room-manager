<?php

namespace App\Http\Controllers;

use App\Models\BusinessPark;
use Illuminate\Http\JsonResponse;

class DebugController extends Controller
{
    public function index(): JsonResponse
    {
        // Загружаем первый бизнес-парк со всеми вложенными зависимостями
        $park = BusinessPark::with([
            'managers',
            'halls.photos',
            'halls.pricingRules',
            'halls.equipment', // Многие ко многим
            'halls.bookings.client',
            'halls.bookings.payments'
        ])->first();

        if (!$park) {
            return response()->json(['error' => 'База данных пуста. Запустите сиддер.'], 404);
        }

        return response()->json([
            'message' => 'Связи проверены успешно!',
            'data' => [
                'park_name' => $park->name,
                'total_halls' => $park->halls->count(),
                'first_hall' => [
                    'name' => $park->halls->first()->name,
                    'photos_count' => $park->halls->first()->photos->count(),
                    'equipment' => $park->halls->first()->equipment->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'quantity' => $item->pivot->quantity, // Проверка pivot таблицы
                            'pivot_id' => $item->pivot->id        // Проверка UUID в pivot
                        ];
                    }),
                    'bookings' => $park->halls->first()->bookings->map(function ($booking) {
                        return [
                            'client' => $booking->client->full_name,
                            'amount' => $booking->total_price,
                            'is_paid' => $booking->payments->where('payment_status', 'paid')->count() > 0
                        ];
                    })
                ]
            ]
        ]);
    }
}