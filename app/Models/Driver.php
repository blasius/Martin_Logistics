<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = [
        'user_id',
        'phone',
        'whatsapp_phone',
        'driving_licence',
        'licence_expiry',
        'licence_file',
        'passport_number',
        'passport_expiry',
        'passport_file',
        'nationality',
        'sex',
        'date_of_birth'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute()
    {
        return $this->user?->name; // convenience accessor
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'driver_vehicle_assignments')
            ->withPivot('assigned_at', 'unassigned_at')
            ->withTimestamps();
    }

    /**
     * Get all trips associated with the driver.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    /**
     * Get the user record associated with the driver.
     */
}
