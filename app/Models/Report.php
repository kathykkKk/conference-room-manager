<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasUuids, HasFactory;
    protected $fillable = ['manager_id', 'report_type', 'file_url'];

    public function manager() {
        return $this->belongsTo(Manager::class);
    }
}
