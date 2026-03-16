<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HallPricingRule extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['hall_id', 'priority', 'price_per_hour', 'apply_from_date', 'apply_until_date', 'weekdays', 'time_from', 'time_to'];

    protected $casts = [
        'apply_from_date' => 'date',
        'apply_until_date' => 'date',
    ];

    public function hall() {
        return $this->belongsTo(Hall::class);
    }
}
