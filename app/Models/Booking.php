<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['hall_id', 'client_id', 'start_datetime', 'end_datetime', 'total_price', 'status'];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public function hall() {
        return $this->belongsTo(Hall::class);
    }

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }

    public function changes() {
        return $this->hasMany(BookingChange::class);
    }
}