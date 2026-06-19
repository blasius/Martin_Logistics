<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairAssignment extends Model
{
    protected $fillable = [
        'repair_request_id', 'mechanic_id',
        'assigned_at', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}
