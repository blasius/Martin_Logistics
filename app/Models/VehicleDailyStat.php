<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class VehicleDailyStat extends Model
{
    protected $fillable = ['vehicle_id', 'date', 'distance_km', 'fuel_consumed'];

    protected $casts = [
        'date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
