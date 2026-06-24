<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TruckRequest extends Model
{
    protected $fillable = [
        'reference',
        'order_id',
        'sales_person_id',
        'cargo_type',
        'tonnage',
        'pickup_location',
        'dropoff_location',
        'expected_pickup_date',
        'expected_delivery_date',
        'special_requirements',
        'agreed_rate',
        'client_reference',
        'payment_status',
        'status',
        'assigned_vehicle_id',
        'assigned_trailer_id',
        'dispatcher_id',
        'trip_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tonnage' => 'decimal:2',
            'agreed_rate' => 'decimal:2',
            'expected_pickup_date' => 'date',
            'expected_delivery_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    public function assignedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function assignedTrailer(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_trailer_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
