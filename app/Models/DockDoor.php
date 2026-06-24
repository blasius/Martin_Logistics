<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DockDoor extends Model
{
    protected $fillable = [
        'code', 'name', 'warehouse_id', 'service_type',
        'is_occupied', 'current_vehicle_id', 'current_queue_id',
        'occupied_since', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
        'is_active' => 'boolean',
        'occupied_since' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function currentVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'current_vehicle_id');
    }

    public function currentQueue()
    {
        return $this->belongsTo(ServiceQueue::class, 'current_queue_id');
    }
}
