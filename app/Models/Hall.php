<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

use Carbon\Carbon;

class Hall extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = ['business_park_id', 'name', 'capacity', 'area_sq_m', 'description', 'status'];

    public function businessPark(): BelongsTo
    {
        return $this->belongsTo(BusinessPark::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HallPhoto::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(HallPricingRule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'hall_equipment')
                    ->withPivot('quantity', 'id')
                    ->withTimestamps();
    }

    public function scopeFilter(Builder $builder, QueryFilter $filter)
    {
        return $filter->apply($builder);
    }

    public function getPriceForTime($dateTime = null)
    {
        $dateTime = $dateTime ? Carbon::parse($dateTime) : Carbon::now();
        $dayOfWeek = (string)$dateTime->dayOfWeekIso; 
        $time = $dateTime->format('H:i:s');

        $rule = $this->pricingRules()
            ->where('apply_from_date', '<=', $dateTime->toDateString())
            ->where(function ($query) use ($dateTime) {
                $query->whereNull('apply_until_date')
                    ->orWhere('apply_until_date', '>=', $dateTime->toDateString());
            })
            ->where(function ($query) use ($dayOfWeek) {
                // Ищем либо конкретный день, либо если правило общее (null)
                $query->whereNull('weekdays')
                    ->orWhere('weekdays', 'like', "%$dayOfWeek%");
            })
            ->orderBy('priority', 'asc')
            ->first();

        // Если нашли спец. правило — берем его цену. 
        // Если нет — берем цену самого ПЕРВОГО правила этого зала (как базовую), 
        // либо фиксированную сумму (например, 1000), чтобы MVP не падал.
        if ($rule) {
            return $rule->price_per_hour;
        }

        $fallbackRule = $this->pricingRules()->first();
        return $fallbackRule ? $fallbackRule->price_per_hour : 1500.00; 
    }
}