<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripFuelAnalysis extends Model
{
    protected $fillable = [
        'trip_id', 'vehicle_id', 'route_id', 'distance_km',
        'fuel_used', 'expected_consumption', 'variance_liters',
        'variance_percent', 'flag', 'analysed_at',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'fuel_used' => 'decimal:2',
            'expected_consumption' => 'decimal:2',
            'variance_liters' => 'decimal:2',
            'variance_percent' => 'decimal:2',
            'analysed_at' => 'datetime',
        ];
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
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
