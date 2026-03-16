<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessPark extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = ['name', 'address', 'city'];

    public function halls(): HasMany {
        return $this->hasMany(Hall::class);
    }

    public function managers(): HasMany {
        return $this->hasMany(Manager::class);
    }
}