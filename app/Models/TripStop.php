<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripStop extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'trip_id', 'vehicle_id', 'latitude', 'longitude',
        'started_at', 'ended_at', 'duration_minutes',
        'classification', 'reason', 'is_off_corridor',
        'distance_from_route_meters', 'alert_sent_at', 'alert_count', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'alert_sent_at' => 'datetime',
        'is_off_corridor' => 'boolean',
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