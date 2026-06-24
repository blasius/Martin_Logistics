<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelDelivery extends Model
{
    protected $fillable = [
        'tank_id', 'supplier_id', 'fuel_type', 'quantity', 'unit_price',
        'total_amount', 'invoice_reference', 'delivered_at', 'received_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'delivered_at' => 'datetime',
        ];
    }

    public function tank()
    {
        return $this->belongsTo(FuelTank::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Vendor::class, 'supplier_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
