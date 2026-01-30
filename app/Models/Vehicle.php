<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'make',
        'model',
        'year',
        'color',
        'status',
        'capacity',
        'capacity_unit',
    ];

    protected $casts = [
        'year' => 'integer',
        'capacity' => 'decimal:2',
        'status' => 'string',
        'last_fine_check_at' => 'datetime',
    ];

    public function inspections()
    {
        return $this->hasMany(VehicleInspection::class);
    }

    public function latestInspection()
    {
        return $this->hasOne(VehicleInspection::class)->latestOfMany();
    }

    public function trailerAssignments()
    {
        return $this->hasMany(TrailerAssignment::class);
    }

    public function currentTrailer()
    {
        return $this->hasOne(TrailerAssignment::class)
            ->latestOfMany('assigned_at')
            ->with('trailer');
    }
    public function trailers()
    {
        return $this->belongsToMany(Trailer::class, 'trailer_assignments')
            ->withTimestamps()
            ->withPivot('assigned_at', 'unassigned_at');
    }

    public function latestTrailer()
    {
        return $this->belongsToMany(Trailer::class, 'trailer_assignments')
            ->withPivot('assigned_at', 'unassigned_at')
            ->whereNull('unassigned_at') // only currently attached
            ->latest('assigned_at');
    }
    public function wialonUnit()
    {
        return $this->hasOne(WialonUnit::class);
    }

    public function trafficFines()
    {
        return $this->morphMany(TrafficFine::class, 'fineable');
    }

    public function latestFine()
    {
        return $this->morphOne(TrafficFine::class, 'fineable')->latestOfMany();
    }

    public function assignments() {
        return $this->hasMany(TrailerAssignment::class);
    }

    public function currentAssignment() {
        return $this->hasOne(TrailerAssignment::class)->whereNull('unassigned_at');
    }

    // In BOTH models, use this exact syntax:
    public function insurances()
    {
        return $this->morphMany(VehicleInsurance::class, 'insurable');
    }

    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'driver_vehicle_assignments')
            ->withPivot('start_date', 'end_date') // ONLY these two
            ->withTimestamps(); // Only if you have created_at/updated_at
    }

}
