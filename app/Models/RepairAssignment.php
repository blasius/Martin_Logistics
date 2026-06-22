<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairAssignment extends Model
{
    protected $fillable = [
        'repair_request_id', 'mechanic_id',
        'instructions', 'status', 'completed_note',
        'assigned_at', 'started_at', 'completed_at',
    ];

    protected $appends = ['duration'];

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

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) return null;

        $end = $this->completed_at ?? now();
        $minutes = (int) $end->diffInMinutes($this->started_at);

        if ($minutes < 60) return "{$minutes}m";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins ? "{$hours}h {$mins}m" : "{$hours}h";
    }
}
