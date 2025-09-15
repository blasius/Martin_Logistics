<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'order_id', 'vehicle_id', 'driver_id',
        'status', 'departure_time', 'arrival_time',
        'vehicle_plate_snapshot', 'driver_name_snapshot', 'trailer_plate_snapshot',
        'created_by',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function histories()
    {
        return $this->hasMany(TripHistory::class);
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($trip) {
            if (! $trip->driver_id && ! $trip->vehicle_id) {
                throw new \Exception('A trip must have either a driver or a vehicle assigned.');
            }
        });
    }

}

