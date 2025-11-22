<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FineCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'plate_number','result','ticket_count','total_amount','response','created_at'
    ];

    protected $casts = [
        'response' => 'array',
        'created_at' => 'datetime',
    ];

    public function checked()
    {
        return $this->morphTo();
    }
}
