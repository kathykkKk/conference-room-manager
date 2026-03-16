<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Manager extends Authenticatable 
{
    use HasUuids, HasFactory, HasApiTokens, Notifiable;

    protected $fillable = ['business_park_id', 'full_name', 'email', 'phone', 'active'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function businessPark() {
        return $this->belongsTo(BusinessPark::class);
    }
}
