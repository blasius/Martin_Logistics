<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleRouteFuelRatio extends Model
{
    protected $table = 'vehicle_route_fuel_ratios';

    protected $fillable = [
        'vehicle_id', 'route_id', 'km_per_liter',
        'effective_from', 'effective_to', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'km_per_liter' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
