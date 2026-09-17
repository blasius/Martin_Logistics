<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripFlowTransition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'from_state_id',
        'to_state_id',
        'code',
        'label',
        'trigger',
        'roles',
        'records_departure_time',
        'records_arrival_time',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'roles' => 'array',
        'records_departure_time' => 'boolean',
        'records_arrival_time' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const TRIGGERS = ['any', 'driver', 'dispatcher', 'system', 'manual'];

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(TripFlowState::class, 'from_state_id');
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(TripFlowState::class, 'to_state_id');
    }
}