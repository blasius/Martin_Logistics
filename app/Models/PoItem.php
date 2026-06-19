<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'part_id', 'description',
        'quantity', 'unit_price', 'total', 'received_quantity',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
