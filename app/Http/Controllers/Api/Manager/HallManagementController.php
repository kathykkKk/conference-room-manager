<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HallManagementController extends Controller
{
    // Список залов менеджера
    public function index(Request $request)
    {
        return Hall::where('business_park_id', $request->user()->business_park_id)
            ->with(['photos', 'equipment'])
            ->get();
    }

    // Добавление нового зала (ТЗ 3.3.2)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'capacity' => 'required|integer|min:0',
            'area_sq_m' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,unavailable',
        ]);

        // Привязываем зал к бизнес-парку менеджера автоматически
        $validated['business_park_id'] = $request->user()->business_park_id;

        $hall = Hall::create($validated);

        return response()->json([
            'message' => 'Зал успешно добавлен',
            'hall' => $hall
        ], 201);
    }

    // Редактирование зала (ТЗ 3.3.2 расширение 3a)
    public function update(Request $request, Hall $hall)
    {
        // Проверка прав (Б-02)
        if ($hall->business_park_id !== $request->user()->business_park_id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:120',
            'capacity' => 'integer|min:0',
            'area_sq_m' => 'numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'in:available,unavailable',
        ]);

        $hall->update($validated);

        return response()->json(['message' => 'Данные зала обновлены', 'hall' => $hall]);
    }

    // Деактивация зала (ТЗ 3.3.2 расширение 3b)
    public function destroy(Request $request, Hall $hall)
    {
        if ($hall->business_park_id !== $request->user()->business_park_id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        // Вместо удаления меняем статус на "недоступен"
        $hall->update(['status' => 'unavailable']);

        return response()->json(['message' => 'Зал деактивирован']);
    }
}