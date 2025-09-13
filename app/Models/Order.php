<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'reference',
        'origin',
        'destination',
        'pickup_date',
        'status',
        'price',
        'notes',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->reference)) {
                // Get the last order ID and increment
                $lastId = static::max('id') ?? 0;
                $nextNumber = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);

                $order->reference = 'ORD-' . $nextNumber;
            }
        });
    }
}
