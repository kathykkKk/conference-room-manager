<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HallEquipment extends Model
{
    use HasUuids, HasFactory;

    // Явно указываем имя таблицы, так как Laravel может попытаться 
    // искать "hall_equipments", а в миграции у нас "hall_equipment"
    protected $table = 'hall_equipment';

    protected $fillable = [
        'hall_id',
        'equipment_id',
        'quantity'
    ];

    /**
     * Связь с залом
     */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /**
     * Связь с оборудованием
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}