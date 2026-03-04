<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelemetryEvent extends Model
{
    protected $fillable = ['vehicle_id', 'telemetry_point_id', 'type', 'value', 'meta', 'occurred_at'];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta' => 'array',
        'value' => 'float',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
