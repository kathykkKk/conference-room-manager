<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\BusinessPark;
use App\Models\Equipment;
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
            'equipment_categories' => Equipment::distinct()->pluck('category'),
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

        $halls = Hall::with(['businessPark', 'photos', 'equipment', 'pricingRules'])
            ->filter($filter)
            ->paginate($per_page);
        
        $halls->getCollection()->transform(function ($hall) {
            $hall->current_price = $hall->getPriceForTime();
            return $hall;
        });

        return response()->json($halls);
    }

    public function show(Hall $hall): JsonResponse
    {
        return response()->json($hall->load(['businessPark', 'photos', 'equipment', 'pricingRules']));
    }
}