<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairRequest extends Model
{
    protected $fillable = [
        'reference', 'vehicle_id', 'mechanic_id', 'driver_id',
        'type', 'priority', 'description', 'status', 'submitted_at',
        'approval_requested_at', 'approval_requested_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approval_requested_at' => 'datetime',
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items()
    {
        return $this->hasMany(RepairRequestItem::class);
    }

    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function assignments()
    {
        return $this->hasMany(RepairAssignment::class);
    }

    public function approvalRequester()
    {
        return $this->belongsTo(User::class, 'approval_requested_by');
    }

    public function release()
    {
        return $this->hasOne(RepairRelease::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
