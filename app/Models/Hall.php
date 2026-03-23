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

    /**
     * Временные слоты (часы) для отображения доступности. 8:00–23:00, по часу.
     * Последний слот 22:00–23:00.
     */
    public static function getTimeSlots(): array
    {
        $slots = [];
        for ($h = 8; $h <= 22; $h++) {
            $slots[] = [
                'start' => sprintf('%02d:00', $h),
                'end' => sprintf('%02d:00', $h + 1),
                'label' => sprintf('%02d:00–%02d:00', $h, $h + 1),
            ];
        }
        return $slots;
    }

    /**
     * Сегодняшний день: слоты, начинающиеся раньше 13:00, недоступны для брони (как «занятые»).
     */
    public static function isSlotBlockedByMorningPolicy(Carbon $day, array $slot): bool
    {
        if (!$day->isSameDay(Carbon::now())) {
            return false;
        }
        $startHour = (int) substr($slot['start'], 0, 2);

        return $startHour < 13;
    }

    /**
     * Пересечение интервала брони с «утренним» окном блокировки в текущие сутки (08:00–13:00).
     */
    public static function intervalOverlapsMorningPolicyWindow(Carbon $start, Carbon $end): bool
    {
        $today = Carbon::now()->startOfDay();
        if (!$start->isSameDay($today)) {
            return false;
        }
        $blockStart = $today->copy()->setTimeFromTimeString('08:00');
        $blockEnd = $today->copy()->setTimeFromTimeString('13:00');

        return $start->lt($blockEnd) && $end->gt($blockStart);
    }

    /**
     * Пересечение интервала брони с часовым слотом (сравнение по Unix time — без сдвига из‑за TZ при сравнении Carbon).
     */
    public static function bookingOverlapsSlot(
        Carbon $bookingStart,
        Carbon $bookingEnd,
        Carbon $slotStart,
        Carbon $slotEnd
    ): bool {
        return $bookingStart->getTimestamp() < $slotEnd->getTimestamp()
            && $bookingEnd->getTimestamp() > $slotStart->getTimestamp();
    }

    /**
     * Матрица доступности: дни × слоты. true = свободно, false = занято.
     * Прошедшие слоты всегда заняты (в т.ч. последний слот 22:00–23:00 прошедшего дня).
     * @param string $fromDate Y-m-d
     * @param int $days количество дней
     */
    public function getAvailabilityMatrix(string $fromDate, int $days = 7): array
    {
        $slots = self::getTimeSlots();
        $tz = config('app.timezone');
        $from = Carbon::parse($fromDate, $tz)->startOfDay();
        $rangeEnd = $from->copy()->addDays($days)->endOfDay();
        $now = Carbon::now($tz);
        $matrix = [];

        $occupiedBookings = $this->bookings()
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->where('start_datetime', '<', $rangeEnd)
            ->where('end_datetime', '>', $from)
            ->get();

        for ($d = 0; $d < $days; $d++) {
            $day = $from->copy()->addDays($d);
            $dayStr = $day->format('Y-m-d');
            $matrix[$dayStr] = [];

            foreach ($slots as $slot) {
                $slotStart = Carbon::parse($dayStr . ' ' . $slot['start'], $tz);
                $slotEnd = Carbon::parse($dayStr . ' ' . $slot['end'], $tz);

                $isPastSlot = $slotEnd->lte($now);
                if ($isPastSlot) {
                    $matrix[$dayStr][] = false;
                    continue;
                }

                if (self::isSlotBlockedByMorningPolicy($day, $slot)) {
                    $matrix[$dayStr][] = false;
                    continue;
                }

                $isOccupied = false;
                foreach ($occupiedBookings as $b) {
                    if (self::bookingOverlapsSlot($b->start_datetime, $b->end_datetime, $slotStart, $slotEnd)) {
                        $isOccupied = true;
                        break;
                    }
                }

                $matrix[$dayStr][] = !$isOccupied;
            }
        }

        return $matrix;
    }

    /**
     * Есть ли хотя бы один свободный слот в ближайшие $weeks недель.
     * Учитываются только будущие слоты (не прошедшие).
     */
    public function hasFreeSlotsInWeeks(int $weeks = 4): bool
    {
        $tz = config('app.timezone');
        $from = Carbon::now($tz)->startOfDay();
        $rangeEnd = $from->copy()->addDays($weeks * 7)->endOfDay();
        $days = $weeks * 7;
        $slots = self::getTimeSlots();
        $now = Carbon::now($tz);

        $occupiedBookings = $this->bookings()
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->where('start_datetime', '<', $rangeEnd)
            ->where('end_datetime', '>', $from)
            ->get();

        for ($d = 0; $d < $days; $d++) {
            $day = $from->copy()->addDays($d);
            foreach ($slots as $slot) {
                $dayStrInner = $day->format('Y-m-d');
                $slotStart = Carbon::parse($dayStrInner . ' ' . $slot['start'], $tz);
                $slotEnd = Carbon::parse($dayStrInner . ' ' . $slot['end'], $tz);
                if ($slotEnd->lte($now)) {
                    continue;
                }
                if (self::isSlotBlockedByMorningPolicy($day, $slot)) {
                    continue;
                }
                $isOccupied = false;
                foreach ($occupiedBookings as $b) {
                    if (self::bookingOverlapsSlot($b->start_datetime, $b->end_datetime, $slotStart, $slotEnd)) {
                        $isOccupied = true;
                        break;
                    }
                }
                if (!$isOccupied) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Есть ли хотя бы один свободный слот в указанный день.
     */
    public function hasFreeSlotOnDate(string $date): bool
    {
        $matrix = $this->getAvailabilityMatrix($date, 1);
        $dayData = $matrix[$date] ?? [];
        return in_array(true, $dayData, true);
    }
}