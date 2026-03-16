<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingChange extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = [
        'booking_id', 'changed_by_manager_id', 'change_type', 
        'old_start_datetime', 'old_end_datetime', 'old_total_price',
        'new_start_datetime', 'new_end_datetime', 'new_total_price',
        'payment_adjustment_amount', 'reason'
    ];

    protected $casts = [
        'old_start_datetime' => 'datetime',
        'old_end_datetime' => 'datetime',
        'new_start_datetime' => 'datetime',
        'new_end_datetime' => 'datetime',
    ];

    public function booking() {
        return $this->belongsTo(Booking::class);
    }

    public function manager() {
        return $this->belongsTo(Manager::class, 'changed_by_manager_id');
    }
}
