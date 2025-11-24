<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficFine extends Model
{
    protected $fillable = [
        'ticket_number','ticket_amount','late_fee','paid_amount','issued_at','pay_by',
        'status','paid_at','payment_reference','payment_method','payment_status',
        'plate_number','location','raw'
    ];

    protected $casts = [
        'issued_at' => 'date',
        'pay_by' => 'date',
        'paid_at' => 'datetime',
        'raw' => 'array',
    ];

    public function fineable()
    {
        return $this->morphTo();
    }

    public function violations()
    {
        return $this->hasMany(TrafficFineViolation::class);
    }
}
