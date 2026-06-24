<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceQueue extends Model
{
    protected $fillable = [
        'vehicle_id', 'service_type', 'priority', 'status',
        'entered_at', 'started_at', 'completed_at',
        'submitted_by', 'coordinates', 'geofence_verified', 'position',
        'assigned_station', 'driver_phone',
    ];

    protected function casts(): array
    {
        return [
            'entered_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'coordinates' => 'array',
            'geofence_verified' => 'boolean',
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
