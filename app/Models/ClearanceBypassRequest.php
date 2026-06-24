<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClearanceBypassRequest extends Model
{
    protected $fillable = [
        'trip_id',
        'check_name',
        'check_label',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'status',
        'manager_comment',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
