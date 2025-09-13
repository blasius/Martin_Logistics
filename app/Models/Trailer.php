<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trailer extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'chassis_number',
        'capacity_weight',
        'type',
        'status',
    ];

    // Scope for active trailers
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function assignments()
    {
        return $this->hasMany(TrailerAssignment::class);
    }

    public function currentVehicle()
    {
        return $this->assignments()->whereNull('unassigned_at')->latest('assigned_at')->first();
    }

}
