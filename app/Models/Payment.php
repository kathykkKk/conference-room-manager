<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['booking_id', 'amount', 'payment_status'];

    public function booking() {
        return $this->belongsTo(Booking::class);
    }

    public function refund() {
        return $this->hasOne(Refund::class);
    }
}