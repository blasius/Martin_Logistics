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
                $year = now()->year;

                // Get the last reference for the current year
                $lastReference = static::where('reference', 'like', "ORD-{$year}-%")
                    ->orderByDesc('id')
                    ->value('reference');

                if ($lastReference) {
                    // Extract the numeric part
                    $lastNumber = intval(substr($lastReference, -5));
                } else {
                    $lastNumber = 0;
                }

                $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

                $order->reference = "ORD-{$year}-{$nextNumber}";
            }
        });
    }
}
