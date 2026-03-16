<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Refund extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['payment_id', 'amount', 'reason', 'status', 'processed_by_manager_id', 'processed_at'];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function payment() {
        return $this->belongsTo(Payment::class);
    }

    public function manager() {
        return $this->belongsTo(Manager::class, 'processed_by_manager_id');
    }
}
