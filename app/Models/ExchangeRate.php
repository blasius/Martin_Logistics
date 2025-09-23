<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'valid_from',
        'valid_to',
        'created_by',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to'   => 'datetime',
    ];

    /**
     * Scope to get rate valid at given datetime
     */
    public function scopeValidAt($query, string $base, string $target, \DateTimeInterface $date)
    {
        return $query->where('base_currency', strtoupper($base))
            ->where('target_currency', strtoupper($target))
            ->where('valid_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            })
            ->orderByDesc('valid_from');
    }
}
