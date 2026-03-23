<?php

namespace App\Filters;

use App\Models\Hall;
use Carbon\Carbon;

class HallFilter extends QueryFilter
{
    // Фильтр по ID бизнес-парка
    public function business_park_id($id)
    {
        return $this->builder->where('business_park_id', $id);
    }
    // Фильтр по ID бизнес-парка
    public function business_park_ids($ids)
    {
        return $this->builder->whereIn('business_park_id', $ids);
    }

    // Фильтр по минимальной вместимости
    public function capacity_from($min)
    {
        return $this->builder->where('capacity', '>=', $min);
    }
    public function capacity_to($max)
    {
        return $this->builder->where('capacity', '<=', $max);
    }

    // Фильтр по городу (один или несколько)
    public function city($cities)
    {
        $cities = is_array($cities) ? $cities : (array) $cities;
        $cities = array_filter(array_map('trim', $cities));
        if (empty($cities)) return $this->builder;

        return $this->builder->whereHas('businessPark', function ($q) use ($cities) {
            $q->whereIn('city', $cities);
        });
    }

    // Фильтр по списку ID оборудования (принимает массив или строку через запятую)
    public function equipment($ids)
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        return $this->builder->whereHas('equipment', function ($q) use ($ids) {
            $q->whereIn('equipment.id', $ids);
        });
    }

    // Фильтр по категориям оборудования
    public function equipment_category($categories)
    {
        $categories = is_array($categories) ? $categories : explode(',', $categories);
        return $this->builder->whereHas('equipment', function ($q) use ($categories) {
            $q->whereIn('equipment.category', $categories);
        });
    }

    // Фильтр по доступности на даты (самая важная часть MVP 3.1)
    // Ожидает массив ['start' => '...', 'end' => '...']
    public function date_range($range)
    {
        if (!isset($range['start']) || !isset($range['end'])) {
            return $this->builder;
        }

        $tz = config('app.timezone');
        $start = Carbon::parse($range['start'], $tz);
        $end = Carbon::parse($range['end'], $tz);

        if (Hall::intervalOverlapsMorningPolicyWindow($start, $end)) {
            return $this->builder->whereRaw('1 = 0');
        }

        // Зал подходит, если выбранный интервал не пересекается ни с одним бронированием
        return $this->builder->whereDoesntHave('bookings', function ($q) use ($start, $end) {
            $q->whereIn('status', ['confirmed', 'pending', 'completed'])
              ->where('start_datetime', '<', $end)
              ->where('end_datetime', '>', $start);
        });
    }

    /**
     * Фильтр по дате: залы, у которых есть хотя бы один свободный слот в этот день
     * (та же сетка, что Hall::getTimeSlots()). Прошедшие слоты не считаются.
     * Параметр: date=YYYY-MM-DD
     */
    public function date($dateStr)
    {
        $tz = config('app.timezone');
        $day = Carbon::parse($dateStr, $tz)->startOfDay();
        $now = Carbon::now($tz);
        $slots = Hall::getTimeSlots();

        $dayStrForSlots = $day->format('Y-m-d');

        return $this->builder->where(function ($outer) use ($day, $now, $slots, $tz, $dayStrForSlots) {
            $first = true;
            foreach ($slots as $slot) {
                $slotStart = Carbon::parse($dayStrForSlots . ' ' . $slot['start'], $tz);
                $slotEnd = Carbon::parse($dayStrForSlots . ' ' . $slot['end'], $tz);
                if ($slotEnd->lte($now)) {
                    continue;
                }
                if (Hall::isSlotBlockedByMorningPolicy($day, $slot)) {
                    continue;
                }
                $callback = function ($q) use ($slotStart, $slotEnd) {
                    $q->whereDoesntHave('bookings', function ($bq) use ($slotStart, $slotEnd) {
                        $bq->whereIn('status', ['confirmed', 'pending', 'completed'])
                            ->where('start_datetime', '<', $slotEnd)
                            ->where('end_datetime', '>', $slotStart);
                    });
                };
                if ($first) {
                    $outer->where($callback);
                    $first = false;
                } else {
                    $outer->orWhere($callback);
                }
            }
            if ($first) {
                $outer->whereRaw('1 = 0');
            }
        });
    }

    public function min_price($price)
    {
        return $this->builder->whereHas('pricingRules', function ($q) use ($price) {
            $q->where('price_per_hour', '>=', $price);
        });
    }

    // Фильтр по максимальной цене
    public function max_price($price)
    {
        return $this->builder->whereHas('pricingRules', function ($q) use ($price) {
            $q->where('price_per_hour', '<=', $price);
        });
    }
}