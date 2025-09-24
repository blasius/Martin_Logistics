<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionProof extends Model
{
    protected $fillable = [
        'requisition_id',
        'uploaded_by',
        'path',
        'mime',
        'type',
        'notes',
    ];

    public function requisition() { return $this->belongsTo(Requisition::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
