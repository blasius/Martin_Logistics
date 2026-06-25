<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'currency_id',
        'current_balance',
        'last_settled_at',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'last_settled_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function settlements()
    {
        return $this->hasMany(WalletSettlement::class);
    }
}
