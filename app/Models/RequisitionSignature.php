<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionSignature extends Model
{
    protected $fillable = [
        'requisition_id',
        'signed_by',
        'role',
        'signature_type',
        'signature_data',
        'signed_at',
    ];

    protected $dates = ['signed_at'];

    public function requisition() { return $this->belongsTo(Requisition::class); }
    public function signer() { return $this->belongsTo(User::class, 'signed_by'); }
}
