<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = [
        'sku', 'name', 'description', 'category', 'unit_of_measure',
        'unit_price', 'compatible_vehicle_makes',
    ];

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
