<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateCardItem extends Model
{
    protected $fillable = [
        'rate_card_id',
        'charge_name',
        'charge_type',
        'amount',
        'currency_id',
        'min_charge',
        'max_charge',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'min_charge' => 'decimal:2',
            'max_charge' => 'decimal:2',
        ];
    }

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
