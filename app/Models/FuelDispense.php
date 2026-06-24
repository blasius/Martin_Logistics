<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelDispense extends Model
{
    protected $fillable = [
        'vehicle_id', 'driver_id', 'tank_id', 'quantity',
        'odometer_at_dispense', 'dispensed_at', 'dispensed_by',
        'trip_id', 'route_id', 'calculated_amount', 'override_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'odometer_at_dispense' => 'decimal:1',
            'dispensed_at' => 'datetime',
            'calculated_amount' => 'decimal:2',
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function tank()
    {
        return $this->belongsTo(FuelTank::class);
    }

    public function dispenser()
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
