<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Container extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'container_id',
        'size',
        'type',
        'owner_type',
        'shipping_line_contract_id',
        'is_owned',
        'purchase_value',
        'purchase_date',
        'current_status',
        'current_location_type',
        'current_location_id',
        'last_known_gps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_owned' => 'boolean',
            'is_active' => 'boolean',
            'purchase_value' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function contract()
    {
        return $this->belongsTo(ShippingLineContract::class, 'shipping_line_contract_id');
    }

    public function currentLocation(): MorphTo
    {
        return $this->morphTo('current_location');
    }

    public function movements()
    {
        return $this->hasMany(ContainerMovement::class);
    }

    public function penalties()
    {
        return $this->hasMany(ContainerPenalty::class);
    }
}
