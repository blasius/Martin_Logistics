<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleSnapshot extends Model
{
    protected $primaryKey = 'vehicle_id';
    public $incrementing = false;

    // Hide the binary column to prevent UTF-8 errors
    protected $hidden = ['location'];

    // Add these to the JSON output automatically
    protected $appends = ['coordinates'];

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

    public function getCoordinatesAttribute()
    {
        // Return a clean array for your Vue Google Maps/Leaflet component
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}
