<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'requisition_id',
        'paid_by_user_id',
        'amount',
        'currency_id',
        'method',
        'tx_reference',
        'paid_at',
        'notes',
    ];

    protected $dates = ['paid_at'];

    public function requisition() { return $this->belongsTo(Requisition::class); }
    public function cashier() { return $this->belongsTo(User::class, 'paid_by_user_id'); }
}
