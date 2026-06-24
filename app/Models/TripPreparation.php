<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPreparation extends Model
{
    protected $fillable = [
        'trip_id',
        'fuel_confirmed',
        'fuel_liters',
        'odometer_start',
        'documents_uploaded',
        'instructions_provided',
        'inspection_confirmed',
        'prepared_by',
        'ready_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fuel_confirmed' => 'boolean',
            'fuel_liters' => 'decimal:2',
            'odometer_start' => 'decimal:2',
            'documents_uploaded' => 'boolean',
            'instructions_provided' => 'boolean',
            'inspection_confirmed' => 'boolean',
            'ready_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->fuel_confirmed
            && $this->documents_uploaded
            && $this->instructions_provided
            && $this->inspection_confirmed;
    }
}
