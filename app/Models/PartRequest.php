<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartRequest extends Model
{
    protected $fillable = [
        'reference', 'repair_request_id', 'part_id', 'quantity',
        'urgency', 'status', 'current_approval_level',
        'requested_by', 'requested_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'current_approval_level' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $partRequest) {
            if (empty($partRequest->reference)) {
                $year = now()->year;
                $last = static::whereYear('created_at', $year)
                    ->orderBy('id', 'desc')
                    ->value('reference');
                $next = $last ? (int) substr($last, -4) + 1 : 1;
                $partRequest->reference = 'PR-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvals()
    {
        return $this->hasMany(PartRequestApproval::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
