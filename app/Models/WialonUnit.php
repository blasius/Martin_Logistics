<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WialonUnit extends Model
{
    protected $fillable = [
        'vehicle_id', 'wialon_id', 'name', 'uid', 'device_type', 'is_linked'
    ];

    protected $casts = [
        'is_linked' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
