<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['client_type', 'full_name', 'email', 'phone', 'company_name'];

    public function bookings() {
        return $this->hasMany(Booking::class);
    }
}
