<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return $this->trailerAssignments()->whereNull('unassigned_at')->latest('assigned_at')->first();
    }

}
