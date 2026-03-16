<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function incomeReport(Request $request)
    {
        $manager = $request->user();
        
        // Параметры периода
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $report = Booking::join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->where('halls.business_park_id', $manager->business_park_id) // Фильтр по парку менеджера
            ->whereBetween('bookings.created_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw("TO_CHAR(bookings.created_at, 'YYYY-MM') as period"),
                'halls.name as hall_name',
                DB::raw("COUNT(bookings.id) as total_bookings"),
                DB::raw("SUM(CASE WHEN bookings.status != 'cancelled' THEN total_price ELSE 0 END) as total_sum"),
                DB::raw("SUM(CASE WHEN bookings.status = 'confirmed' THEN total_price ELSE 0 END) as paid_sum"),
                DB::raw("SUM(CASE WHEN bookings.status = 'cancelled' THEN total_price * 0.1 ELSE 0 END) as refund_penalty")
            )
            ->groupBy(DB::raw("period"), 'halls.name')
            ->get();

        $data = $report->map(function ($item) {
            $netIncome = (float)$item->paid_sum - (float)$item->refund_penalty;
            return [
                'period' => $item->period,
                'hall' => $item->hall_name,
                'count' => (int)$item->total_bookings,
                'total_sum' => number_format((float)$item->total_sum, 2, '.', ''),
                'paid' => number_format((float)$item->paid_sum, 2, '.', ''),
                'net_income' => number_format($netIncome, 2, '.', ''),
                'average_check' => $item->total_bookings > 0 
                    ? number_format($netIncome / $item->total_bookings, 2, '.', '') 
                    : "0.00"
            ];
        });

        return response()->json([
            'report_id' => 'REP-INCOME-01',
            'title' => 'Отчет по доходам за период',
            'business_park' => $manager->business_park->name ?? 'Базовое значение парка (тест)',
            'currency' => 'RUB',
            'data' => $data
        ]);
    }
}