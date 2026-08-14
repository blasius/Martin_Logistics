<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class ProofOfDelivery extends Model
{
    use HasAuditTrail;

    protected $table = 'proofs_of_delivery';

    protected $fillable = [
        'order_id',
        'trip_id',
        'delivered_at',
        'received_by_name',
        'received_by_relation',
        'signature_data',
        'photo_path',
        'gps_lat',
        'gps_lng',
        'notes',
        'status',
        'submitted_by',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'gps_lat' => 'decimal:7',
        'gps_lng' => 'decimal:7',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
