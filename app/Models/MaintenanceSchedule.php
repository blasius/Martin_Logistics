<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceSchedule extends Model
{
    protected $fillable = [
        'vehicle_id', 'type', 'interval_km', 'interval_days',
        'last_done_at', 'last_done_km', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'interval_km' => 'decimal:1',
            'interval_days' => 'integer',
            'last_done_at' => 'datetime',
            'last_done_km' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
