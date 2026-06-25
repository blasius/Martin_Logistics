<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'days_allocated', 'days_used'];

    protected function casts(): array
    {
        return [
            'days_allocated' => 'decimal:1',
            'days_used' => 'decimal:1',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingDaysAttribute()
    {
        return $this->days_allocated - $this->days_used;
    }
}
