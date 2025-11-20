<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficFine extends Model
{
    protected $fillable = [
        'ticket_number',
        'ticket_amount',
        'late_fee',
        'paid_amount',
        'issued_at',
        'pay_by',
        'status',
        'location',
        'raw'
    ];

    protected $casts = [
        'raw' => 'array',
        'issued_at' => 'date',
        'pay_by' => 'date',
    ];

    public function violations()
    {
        return $this->hasMany(TrafficFineViolation::class);
    }

    public function finedable()
    {
        return $this->morphTo();
    }
}
