<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteDeviationLog extends Model
{
    protected $fillable = [
        'trip_id', 'vehicle_id', 'latitude', 'longitude',
        'distance_from_route_meters', 'detected_at',
        'resolved_at', 'duration_minutes', 'notes',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
