<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Отчёт по завершённым бронированиям за период (по дате начала брони в TZ приложения).
     */
    public function incomeReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $manager = $request->user();
        $tz = config('app.timezone');

        $from = Carbon::parse($validated['date_from'], $tz)->startOfDay();
        $to = Carbon::parse($validated['date_to'], $tz)->endOfDay();

        $bookings = Booking::query()
            ->with(['hall.businessPark', 'payments.refund'])
            ->whereHas('hall', function ($q) use ($manager) {
                $q->where('business_park_id', $manager->business_park_id);
            })
            ->where('status', 'completed')
            ->whereBetween('start_datetime', [$from, $to])
            ->orderBy('start_datetime')
            ->get();

        $rows = $bookings
            ->groupBy(function (Booking $b) use ($tz) {
                $period = $b->start_datetime->timezone($tz)->format('Y-m');

                return $period . '|' . $b->hall_id;
            })
            ->map(function ($group) use ($tz) {
                /** @var \Illuminate\Support\Collection<int, Booking> $group */
                $first = $group->first();
                $hall = $first->hall;
                $parkName = $hall?->businessPark?->name ?? '';

                $count = $group->count();
                $totalSum = (float) $group->sum('total_price');

                $paidSum = 0.0;
                $refundSum = 0.0;

                foreach ($group as $booking) {
                    foreach ($booking->payments as $payment) {
                        if ($payment->payment_status === 'paid') {
                            $paidSum += (float) $payment->amount;
                        }
                        $refund = $payment->refund;
                        if ($refund && $refund->status === 'processed') {
                            $refundSum += (float) $refund->amount;
                        }
                    }
                }

                if ($paidSum <= 0 && $totalSum > 0) {
                    $paidSum = $totalSum;
                }

                $netIncome = $paidSum - $refundSum;
                $paidPct = $totalSum > 0 ? round(($paidSum / $totalSum) * 100, 1) : 0.0;
                $avgCheck = $count > 0 ? round($totalSum / $count, 2) : 0.0;

                $period = $first->start_datetime->timezone($tz)->format('Y-m');

                return [
                    'period' => $period,
                    'hall' => $hall->name ?? '',
                    'business_park' => $parkName,
                    'bookings_count' => $count,
                    'total_bookings_amount' => round($totalSum, 2),
                    'paid_amount' => round($paidSum, 2),
                    'refunds_amount' => round($refundSum, 2),
                    'net_income' => round($netIncome, 2),
                    'paid_percentage' => $paidPct,
                    'average_check' => $avgCheck,
                ];
            })
            ->values()
            ->sortBy(fn (array $row) => $row['period'] . "\0" . $row['hall'])
            ->values();

        return response()->json([
            'report_id' => 'REP-INCOME-01',
            'title' => 'Отчёт по завершённым бронированиям за период',
            'business_park' => $manager->businessPark->name ?? '',
            'currency' => 'RUB',
            'data' => $rows,
        ]);
    }
}
