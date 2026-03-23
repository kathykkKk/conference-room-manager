<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\BusinessPark;
use App\Models\Equipment;
use App\Models\PolicySlotHold;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Filters\HallFilter;

class HallController extends Controller
{
    /**
     * Данные для инициализации фильтров на фронте
     */
    public function getFilters(): JsonResponse
    {
        return response()->json([
            'business_parks' => BusinessPark::select('id', 'name', 'city')->get(),
            'cities' => BusinessPark::distinct()->orderBy('city')->pluck('city'),
            'equipment_categories' => Equipment::distinct()->orderBy('category')->pluck('category'),
            'capacity_ranges' => [
                ['label' => 'до 20 чел', 'min' => 0, 'max' => 20],
                ['label' => '20-50 чел', 'min' => 20, 'max' => 50],
                ['label' => '50+ чел', 'min' => 50, 'max' => 1000],
            ],
            'statuses' => ['available', 'unavailable']
        ]);
    }

    /**
     * Поиск залов (ТЗ раздел 3.1)
     */
    public function index(Request $request, HallFilter $filter): JsonResponse
    {

        $per_page = 10;
        if ($request->per_page == '0') {
            $per_page = Hall::count();
        } else {
            $per_page = $request->per_page;
        }

        $hasFilteredAvailability = $request->filled('date')
            || $request->filled('date_range.start');

        if ($request->filled('date') && $request->date === Carbon::today()->format('Y-m-d')) {
            PolicySlotHold::syncMorningPolicyHoldsForDate(Carbon::today());
        } elseif ($request->filled('date_range.start')) {
            $rangeStart = Carbon::parse($request->input('date_range.start'), config('app.timezone'));
            if ($rangeStart->isSameDay(Carbon::today())) {
                PolicySlotHold::syncMorningPolicyHoldsForDate(Carbon::today());
            }
        }

        $halls = Hall::with(['businessPark', 'photos', 'equipment', 'pricingRules'])
            ->when($hasFilteredAvailability, function ($q) {
                $q->where('status', 'available');
            })
            ->filter($filter)
            ->paginate($per_page);

        $halls->getCollection()->transform(function ($hall) use ($hasFilteredAvailability) {
            $hall->current_price = $hall->getPriceForTime();
            $hall->has_free_slots_4weeks = $hall->hasFreeSlotsInWeeks(4);
            if (!$hall->has_free_slots_4weeks && !$hasFilteredAvailability) {
                $hall->status = 'unavailable';
            }
            $hall->is_bookable = $hall->status === 'available'
                && ($hall->has_free_slots_4weeks || $hasFilteredAvailability);
            return $hall;
        });

        return response()->json($halls);
    }

    public function show(Hall $hall): JsonResponse
    {
        $hall->load(['businessPark', 'photos', 'equipment', 'pricingRules']);
        $hall->current_price = $hall->getPriceForTime();
        $hall->has_free_slots_4weeks = $hall->hasFreeSlotsInWeeks(4);
        if (!$hall->has_free_slots_4weeks) {
            $hall->status = 'unavailable';
        }
        $hall->is_bookable = $hall->status === 'available' && $hall->has_free_slots_4weeks;
        return response()->json($hall);
    }

    /**
     * Матрица доступности зала: дни × слоты (свободно/занято).
     * Для недоступных залов — все ячейки красные (занято).
     * GET /api/v1/halls/{hall}/availability?from=YYYY-MM-DD&days=7
     */
    public function availability(Request $request, Hall $hall): JsonResponse
    {
        $from = $request->get('from', now()->format('Y-m-d'));
        $days = (int) $request->get('days', 7);
        $days = min(max($days, 1), 28);

        PolicySlotHold::syncMorningPolicyHoldsForDate(Carbon::today());

        $slots = Hall::getTimeSlots();
        $forceAllOccupied = $hall->status === 'unavailable' || !$hall->hasFreeSlotsInWeeks(4);
        if ($forceAllOccupied) {
            $matrix = [];
            $fromDate = Carbon::parse($from, config('app.timezone'));
            for ($d = 0; $d < $days; $d++) {
                $dayStr = $fromDate->copy()->addDays($d)->format('Y-m-d');
                $matrix[$dayStr] = array_fill(0, count($slots), false);
            }
        } else {
            $matrix = $hall->getAvailabilityMatrix($from, $days);
        }

        return response()->json([
            'hall_id' => $hall->id,
            'from' => $from,
            'days' => $days,
            'time_slots' => Hall::getTimeSlots(),
            'availability' => $matrix,
        ]);
    }
}