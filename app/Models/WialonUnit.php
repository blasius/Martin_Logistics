<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WialonUnit extends Model
{
    protected $fillable = [
        'vehicle_id',
        'wialon_id',
        'name',
        'uid',
        'device_type',
        'last_lat',
        'last_lon',
        'speed',
        'ignition',
        'gps_voltage',
        'vehicle_voltage',
        'last_update',
        'is_linked'
    ];

    protected $casts = [
        'is_linked' => 'boolean',
        'last_update' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
