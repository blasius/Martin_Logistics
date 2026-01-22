<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'phone',
        'whatsapp_phone',
        'passport_number',
        'driving_licence',
        'nationality',
        'sex',
        'date_of_birth',
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
}
