<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyFineStat extends Model
{
    protected $fillable = ['date','vehicle_id','trailer_id','ticket_count','total_amount'];

    protected $casts = ['date' => 'date'];
}
