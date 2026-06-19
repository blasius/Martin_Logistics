<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairRelease extends Model
{
    protected $fillable = [
        'repair_request_id', 'released_by', 'released_at',
        'odometer_at_release', 'unresolved_issues', 'checklist_completed',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
            'checklist_completed' => 'boolean',
        ];
    }

    public function repairRequest()
    {
        return $this->belongsTo(RepairRequest::class);
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
