<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContainerMovement extends Model
{
    protected $fillable = [
        'container_id',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'movement_type',
        'vehicle_id',
        'driver_id',
        'trip_id',
        'seal_number',
        'departed_at',
        'arrived_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
        ];
    }

    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function fromLocation(): MorphTo
    {
        return $this->morphTo('from_location');
    }

    public function toLocation(): MorphTo
    {
        return $this->morphTo('to_location');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
