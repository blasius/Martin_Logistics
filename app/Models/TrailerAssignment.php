<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrailerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'trailer_id',
        'assigned_at',
        'unassigned_at',
    ];

    protected $dates = [
        'assigned_at',
        'unassigned_at',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function trailer()
    {
        return $this->belongsTo(Trailer::class);
    }

    public function isActive(): bool
    {
        return is_null($this->unassigned_at);
    }
}
