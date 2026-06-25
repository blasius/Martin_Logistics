<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletSettlement extends Model
{
    protected $fillable = [
        'wallet_id',
        'period_start',
        'period_end',
        'balance_at_settlement',
        'amount_settled',
        'method',
        'settled_at',
        'settled_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'balance_at_settlement' => 'decimal:2',
            'amount_settled' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function settler()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }
}
