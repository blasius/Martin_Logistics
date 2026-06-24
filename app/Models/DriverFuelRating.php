<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverFuelRating extends Model
{
    protected $fillable = [
        'driver_id', 'period_start', 'period_end',
        'avg_variance_percent', 'total_trips', 'flagged_trips', 'rating',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'avg_variance_percent' => 'decimal:2',
            'total_trips' => 'integer',
            'flagged_trips' => 'integer',
        ];
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
