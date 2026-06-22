<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartRequestApproval extends Model
{
    protected $fillable = [
        'part_request_id', 'approver_id',
        'approval_level', 'action', 'comment',
    ];

    public function partRequest()
    {
        return $this->belongsTo(PartRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
