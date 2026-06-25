<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'department_id', 'position_id', 'employee_number',
        'first_name', 'last_name', 'email', 'phone', 'hire_date',
        'employment_status', 'emergency_contact_name', 'emergency_contact_phone',
        'salary', 'salary_currency_id', 'bank_name', 'bank_account', 'bank_code',
        'documents', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'salary' => 'decimal:2',
            'documents' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function salaryCurrency()
    {
        return $this->belongsTo(Currency::class, 'salary_currency_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (empty($employee->employee_number)) {
                $year = now()->year;
                $last = static::where('employee_number', 'like', "EMP-{$year}-%")
                    ->orderByDesc('id')->value('employee_number');
                $num = $last ? ((int) substr($last, -5)) + 1 : 1;
                $employee->employee_number = "EMP-{$year}-" . str_pad($num, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
