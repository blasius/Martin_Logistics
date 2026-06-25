<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAuditTrail;

class Order extends Model
{
    use HasFactory, HasAuditTrail;

    protected $fillable = [
        'client_id',
        'contract_id',
        'reference',
        'origin',
        'destination',
        'pickup_date',
        'status',
        'price',
        'currency_id',
        'weight_kg',
        'volume_m3',
        'notes',
        'branch_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function proofOfDelivery()
    {
        return $this->hasOne(ProofOfDelivery::class);
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
