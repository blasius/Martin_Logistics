<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'reference', 'vendor_id', 'repair_request_id', 'order_date',
        'expected_date', 'status', 'notes', 'total_amount', 'currency_id',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
        ];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function items()
    {
        return $this->hasMany(PoItem::class);
    }
}
