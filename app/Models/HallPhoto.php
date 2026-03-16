<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HallPhoto extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['hall_id', 'url'];

    public function hall() {
        return $this->belongsTo(Hall::class);
    }
}
