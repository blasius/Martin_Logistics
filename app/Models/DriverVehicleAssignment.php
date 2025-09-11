<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

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
        static::creating(fn ($assignment) => static::ensureNoActiveConflict($assignment));
        static::updating(fn ($assignment) => static::ensureNoActiveConflict($assignment));
    }

    protected static function ensureNoActiveConflict(DriverVehicleAssignment $assignment): void
    {
        $start = $assignment->start_date ? Carbon::parse($assignment->start_date) : now();

        // Vehicle conflict
        $vehicleConflict = static::query()
            ->where('vehicle_id', $assignment->vehicle_id)
            ->where(function ($q) use ($assignment) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', $assignment->start_date ?? now());
            })
            ->when($assignment->exists, fn ($q) => $q->where('id', '!=', $assignment->id))
            ->exists();

        if ($vehicleConflict) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'This vehicle already has an active assignment during the selected period.',
            ]);
        }

        // Driver conflict
        $driverConflict = static::query()
            ->where('driver_id', $assignment->driver_id)
            ->where(function ($q) use ($assignment) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', $assignment->start_date ?? now());
            })
            ->when($assignment->exists, fn ($q) => $q->where('id', '!=', $assignment->id))
            ->exists();

        if ($driverConflict) {
            throw ValidationException::withMessages([
                'driver_id' => 'This driver already has an active assignment during the selected period.',
            ]);
        }
    }
}
