<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PerformanceScore extends Model
{
    protected $fillable = [
        'scoreable_type',
        'scoreable_id',
        'period_start',
        'period_end',
        'overall_score',
        'fuel_efficiency_score',
        'on_time_delivery_score',
        'route_compliance_score',
        'expense_management_score',
        'safety_score',
        'human_rating_avg',
        'human_rating_count',
        'automated_score',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'calculated_at' => 'datetime',
        ];
    }

    public function scoreable(): MorphTo
    {
        return $this->morphTo();
    }
}
