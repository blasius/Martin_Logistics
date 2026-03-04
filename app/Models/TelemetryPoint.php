<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelemetryPoint extends Model
{
    // Since you changed the primary key to [id, recorded_at] for partitioning
    protected $primaryKey = ['id', 'recorded_at'];
    public $incrementing = false;

    protected $casts = [
        'recorded_at' => 'datetime',
        'ignition' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
