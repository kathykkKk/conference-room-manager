<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // 1. Валидация входных данных
        $validated = $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'start_datetime' => 'required|date|after:now',
            'end_datetime' => 'required|date|after:start_datetime',
            'client_email' => 'required|email',
            'client_full_name' => 'required|string|max:150',
            'client_phone' => 'required|string|max:30',
        ]);

        $start = Carbon::parse($validated['start_datetime']);
        $end = Carbon::parse($validated['end_datetime']);

        // 2. Используем транзакцию для атомарности (ТЗ 6.1, Ц-03)
        return DB::transaction(function () use ($validated, $start, $end) {
            
            // 3. Блокируем зал для проверки (защита от конкурентных запросов)
            $hall = Hall::lockForUpdate()->find($validated['hall_id']);

            // 4. Проверка на овербукинг (ТЗ Ц-01)
            $exists = Booking::where('hall_id', $hall->id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_datetime', [$start, $end])
                          ->orWhereBetween('end_datetime', [$start, $end])
                          ->orWhere(function ($q) use ($start, $end) {
                              $q->where('start_datetime', '<=', $start)
                                ->where('end_datetime', '>=', $end);
                          });
                })->exists();

            if ($exists) {
                return response()->json(['message' => 'Этот временной слот уже занят'], 422);
            }

            // 5. Расчет стоимости
            $pricePerHour = $hall->getPriceForTime($start);
            if (!$pricePerHour) {
                return response()->json(['message' => 'Для данного времени не установлены правила цены'], 422);
            }

            $durationInHours = $start->diffInMinutes($end) / 60;
            $totalPrice = $durationInHours * $pricePerHour;

            // 6. Поиск или создание клиента
            $client = Client::firstOrCreate(
                ['email' => $validated['client_email']],
                [
                    'full_name' => $validated['client_full_name'],
                    'phone' => $validated['client_phone'],
                    'client_type' => 'individual'
                ]
            );

            // 7. Создание бронирования
            $booking = Booking::create([
                'hall_id' => $hall->id,
                'client_id' => $client->id,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'total_price' => $totalPrice,
                'status' => 'pending' // Изначально статус ожидания оплаты
            ]);

            return response()->json([
                'message' => 'Бронирование создано',
                'booking' => $booking->load('hall', 'client')
            ], 201);
        });
    }
}