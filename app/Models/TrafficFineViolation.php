<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficFineViolation extends Model
{
    protected $fillable = ['traffic_fine_id','violation_name','violation_name_fr','violation_name_local','fine_amount','quantity'];

    public function trafficFine()
    {
        return $this->belongsTo(TrafficFine::class);
    }
}
