<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DriverVehicleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    protected static function booted()
    {
        // Before creating: ensure no overlapping active assignment for vehicle or driver
        static::creating(function (DriverVehicleAssignment $assignment) {
            static::ensureNoActiveConflict($assignment);
        });

        // Before updating: ensure conflicts are respected (allow closing an assignment)
        static::updating(function (DriverVehicleAssignment $assignment) {
            static::ensureNoActiveConflict($assignment);
        });
    }

    protected static function ensureNoActiveConflict(DriverVehicleAssignment $assignment): void
    {
        // Normalize start/end
        $start = $assignment->start_date ? Carbon::parse($assignment->start_date) : now();

        // Check (vehicle) existing active assignment
        $vehicleConflict = static::query()
            ->where('vehicle_id', $assignment->vehicle_id)
            ->where(function ($q) use ($assignment) {
                // Active means end_date IS NULL or end_date > start
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', $assignment->start_date ?? now());
            })
            ->when($assignment->exists, fn($q) => $q->where('id', '!=', $assignment->id))
            ->exists();

        if ($vehicleConflict) {
            throw new \RuntimeException('Vehicle already has an active assignment for the selected period.');
        }

        // Check (driver) existing active assignment
        $driverConflict = static::query()
            ->where('driver_id', $assignment->driver_id)
            ->where(function ($q) use ($assignment) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', $assignment->start_date ?? now());
            })
            ->when($assignment->exists, fn($q) => $q->where('id', '!=', $assignment->id))
            ->exists();

        if ($driverConflict) {
            throw new \RuntimeException('Driver already has an active assignment for the selected period.');
        }
    }
}
