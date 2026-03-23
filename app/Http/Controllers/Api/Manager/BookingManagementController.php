<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BookingManagementController extends Controller
{
    /**
     * Список бронирований (только для своего бизнес-парка - ТЗ Б-02)
     */
    public function index(Request $request)
    {
        $manager = $request->user(); // Получаем текущего авторизованного менеджера

        return Booking::whereHas('hall', function ($query) use ($manager) {
            $query->where('business_park_id', $manager->business_park_id);
        })
        ->with(['hall', 'client'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    }

    /**
     * Подтверждение оплаты (ТЗ 3.2.2)
     */
    public function confirm(Request $request, Booking $booking)
    {
        $manager = $request->user();

        // Проверка прав (чтобы менеджер не мог подтвердить чужую бронь)
        if ($booking->hall->business_park_id !== $manager->business_park_id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        $booking->update(['status' => 'confirmed']);

        $booking->load(['hall', 'client']);

        $notifyEmail = config('booking.confirmation_notify_email');
        if (is_string($notifyEmail) && $notifyEmail !== '' && filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
            Mail::to($notifyEmail)->send(new BookingConfirmedMail($booking));
        }

        return response()->json([
            'message' => 'Бронирование подтверждено',
            'booking' => $booking
        ]);
    }

    /**
     * Отмена бронирования
     */
    public function cancel(Request $request, Booking $booking)
    {
        $manager = $request->user();

        if ($booking->hall->business_park_id !== $manager->business_park_id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        $booking->update(['status' => 'cancelled']);
        
        return response()->json(['message' => 'Бронирование отменено']);
    }
}