<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleSnapshot extends Model
{
    protected $primaryKey = 'vehicle_id';
    public $incrementing = false;

    protected $fillable = [
        'vehicle_id', 'last_seen_at', 'latitude', 'longitude',
        'speed', 'fuel_level', 'ignition', 'is_moving', 'low_fuel'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'ignition' => 'boolean',
        'is_moving' => 'boolean',
        'low_fuel' => 'boolean',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
