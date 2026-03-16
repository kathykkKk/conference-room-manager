<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['name', 'category'];

    public function halls() {
        return $this->belongsToMany(Hall::class, 'hall_equipment')->withPivot('quantity');
    }
}
