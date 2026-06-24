<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelTank extends Model
{
    protected $fillable = [
        'code', 'name', 'capacity', 'current_level', 'fuel_type',
        'reorder_threshold', 'last_calibrated_at', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'current_level' => 'decimal:2',
            'reorder_threshold' => 'decimal:2',
            'last_calibrated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function deliveries()
    {
        return $this->hasMany(FuelDelivery::class);
    }

    public function dispenses()
    {
        return $this->hasMany(FuelDispense::class);
    }

    public function isLow(): bool
    {
        return $this->reorder_threshold !== null && $this->current_level <= $this->reorder_threshold;
    }
}
