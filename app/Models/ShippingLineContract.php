<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingLineContract extends Model
{
    protected $fillable = [
        'shipping_line',
        'name',
        'free_demurrage_days',
        'free_detention_days',
        'demurrage_tiers',
        'detention_tiers',
        'currency_id',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'demurrage_tiers' => 'array',
            'detention_tiers' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function containers()
    {
        return $this->hasMany(Container::class);
    }
}
