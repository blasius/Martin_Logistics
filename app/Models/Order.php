<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'reference',
        'origin',
        'destination',
        'pickup_date',
        'status',
        'price',
        'notes',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
