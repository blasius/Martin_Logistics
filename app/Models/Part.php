<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = [
        'sku', 'barcode', 'name', 'description', 'category', 'unit_of_measure',
        'unit_price', 'compatible_vehicle_makes',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $part) {
            if (empty($part->sku)) {
                $year = now()->year;
                $last = static::whereYear('created_at', $year)
                    ->orderBy('id', 'desc')
                    ->value('sku');
                $next = $last ? (int) substr($last, -4) + 1 : 1;
                $part->sku = 'PRT-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function repairRequestItems()
    {
        return $this->hasMany(RepairRequestItem::class);
    }

    public function poItems()
    {
        return $this->hasMany(PoItem::class);
    }
}
