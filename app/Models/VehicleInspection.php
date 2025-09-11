<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleInspection extends Model
{
    protected $fillable = [
        'vehicle_id',
        'scheduled_date',
        'completed_date',
        'inspector_name',
        'document_path',
        'status',
        'replaces_id',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function replaces()
    {
        return $this->belongsTo(VehicleInspection::class, 'replaces_id');
    }

    public function replacedBy()
    {
        return $this->hasOne(VehicleInspection::class, 'replaces_id');
    }

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

}

