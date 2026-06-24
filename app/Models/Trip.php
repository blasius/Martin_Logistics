<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class Trip extends Model
{
    use HasAuditTrail;
    protected $fillable = [
        'order_id', 'vehicle_id', 'driver_id',
        'status', 'departure_time', 'arrival_time',
        'vehicle_plate_snapshot', 'driver_name_snapshot', 'trailer_plate_snapshot',
        'created_by',
        'route_id', 'dispatcher_id',
        'planned_distance_km', 'actual_distance_km',
        'start_odometer', 'end_odometer',
        'is_deviated', 'deviation_detected_at',
        'deviation_duration_minutes', 'deviation_max_distance_meters',
        'auto_ticket_id',
    ];

    protected $casts = [
        'is_deviated' => 'boolean',
        'deviation_detected_at' => 'datetime',
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
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

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function deviationLogs()
    {
        return $this->hasMany(RouteDeviationLog::class);
    }

    public function autoTicket()
    {
        return $this->belongsTo(SupportTicket::class, 'auto_ticket_id');
    }

    public function histories()
    {
        return $this->hasMany(TripHistory::class);
    }

    public function proofOfDelivery()
    {
        return $this->hasOne(ProofOfDelivery::class);
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

