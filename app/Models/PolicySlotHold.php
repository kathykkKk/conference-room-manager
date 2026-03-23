<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PolicySlotHold extends Model
{
    protected $table = 'policy_slot_holds';

    protected $fillable = ['hall_id', 'hold_date', 'slot_start', 'reason'];

    protected $casts = [
        'hold_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /**
     * Фиксирует в БД утренние слоты (до 13:00) на указанную дату для всех залов — как «занято» по политике.
     */
    public static function syncMorningPolicyHoldsForDate(Carbon $day): void
    {
        static::where('hold_date', '<', Carbon::today()->format('Y-m-d'))
            ->where('reason', 'morning_policy')
            ->delete();

        if (!$day->isSameDay(Carbon::now())) {
            return;
        }

        $dayStr = $day->format('Y-m-d');
        $slots = Hall::getTimeSlots();

        static::where('hold_date', $dayStr)->where('reason', 'morning_policy')->delete();

        $rows = [];
        foreach (Hall::query()->pluck('id') as $hallId) {
            foreach ($slots as $slot) {
                $startHour = (int) substr($slot['start'], 0, 2);
                if ($startHour >= 13) {
                    continue;
                }
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'hall_id' => $hallId,
                    'hold_date' => $dayStr,
                    'slot_start' => $slot['start'],
                    'reason' => 'morning_policy',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        if ($rows !== []) {
            foreach (array_chunk($rows, 200) as $chunk) {
                static::insert($chunk);
            }
        }
    }
}
