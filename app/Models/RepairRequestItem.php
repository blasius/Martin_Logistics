<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairRequestItem extends Model
{
    protected $fillable = [
        'repair_request_id', 'description', 'part_id',
        'estimated_quantity', 'estimated_unit_price', 'estimated_total',
        'actual_quantity', 'actual_unit_price', 'actual_total',
    ];

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
