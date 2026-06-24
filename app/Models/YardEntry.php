<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class YardEntry extends Model
{
    use HasAuditTrail;

    protected $fillable = [
        'vehicle_id', 'driver_id', 'check_in_at', 'check_out_at',
        'purpose', 'dock_door_id', 'service_queue_id', 'notes', 'checked_in_by',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function dockDoor()
    {
        return $this->belongsTo(DockDoor::class);
    }

    public function serviceQueue()
    {
        return $this->belongsTo(ServiceQueue::class);
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
