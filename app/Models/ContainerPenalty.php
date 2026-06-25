<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerPenalty extends Model
{
    protected $fillable = [
        'container_id',
        'penalty_type',
        'days_overdue',
        'daily_rate',
        'total_amount',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'calculated_at' => 'datetime',
        ];
    }

    public function container()
    {
        return $this->belongsTo(Container::class);
    }
}
