<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $fillable = [
        'reference',
        'order_id',
        'client_id',
        'status',
        'reason',
        'pickup_address',
        'pickup_date',
        'pickup_trip_id',
        'received_at',
        'disposition',
        'credit_note_id',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'received_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function pickupTrip()
    {
        return $this->belongsTo(Trip::class, 'pickup_trip_id');
    }

    public function creditNote()
    {
        return $this->belongsTo(Invoice::class, 'credit_note_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function booted()
    {
        static::creating(function ($return) {
            if (empty($return->reference)) {
                $year = now()->year;
                $last = static::where('reference', 'like', "RMA-{$year}-%")
                    ->orderByDesc('id')->value('reference');
                $num = $last ? ((int) substr($last, -5)) + 1 : 1;
                $return->reference = "RMA-{$year}-" . str_pad($num, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
