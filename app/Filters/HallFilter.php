<?php

namespace App\Filters;

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

    // Фильтр по городу (через связь с бизнес-парком)
    public function city($city)
    {
        return $this->builder->whereHas('businessPark', function ($q) use ($city) {
            $q->where('city', 'ILIKE', "%$city%");
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
        if (!isset($range['start']) || !isset($range['end'])) return $this->builder;

        $start = $range['start'];
        $end = $range['end'];

        return $this->builder->whereDoesntHave('bookings', function ($q) use ($start, $end) {
            $q->whereIn('status', ['confirmed', 'pending'])
              ->where(function ($inner) use ($start, $end) {
                  $inner->whereBetween('start_datetime', [$start, $end])
                        ->orWhereBetween('end_datetime', [$start, $end])
                        ->orWhere(function ($deep) use ($start, $end) {
                            $deep->where('start_datetime', '<', $start)
                                 ->where('end_datetime', '>', $end);
                        });
              });
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