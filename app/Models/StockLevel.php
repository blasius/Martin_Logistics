<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLevel extends Model
{
    protected $fillable = ['warehouse_id', 'part_id', 'quantity', 'min_quantity'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
