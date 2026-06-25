<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Payslip;
use App\Models\PayPeriod;
use App\Models\Department;
use App\Models\Position;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class HrService
{
    // --- Departments ---
    public function createDepartment(array $data): Department
    {
        return Department::create($data);
    }

    public function updateDepartment(Department $department, array $data): Department
    {
        $department->update($data);
        return $department->fresh();
    }

    // --- Positions ---
    public function createPosition(array $data): Position
    {
        return Position::create($data);
    }

    public function updatePosition(Position $position, array $data): Position
    {
        $position->update($data);
        return $position->fresh();
    }

    // --- Employees ---
    public function createEmployee(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::create($data);
            $this->seedLeaveBalances($employee);
            return $employee->fresh()->load(['department', 'position', 'salaryCurrency', 'user']);
        });
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh()->load(['department', 'position', 'salaryCurrency', 'user']);
    }

    // --- Attendance ---
    public function clockIn(int $employeeId, ?string $time = null): AttendanceRecord
    {
        $date = now()->toDateString();
        $time = $time ?? now()->format('H:i:s');

        $existing = AttendanceRecord::where('employee_id', $employeeId)
            ->where('date', $date)->first();

        if ($existing) {
            $existing->update(['clock_in' => $time]);
            return $existing->fresh();
        }

        return AttendanceRecord::create([
            'employee_id' => $employeeId,
            'date' => $date,
            'clock_in' => $time,
            'status' => 'present',
        ]);
    }

    public function clockOut(int $employeeId, ?string $time = null): AttendanceRecord
    {
        $date = now()->toDateString();
        $time = $time ?? now()->format('H:i:s');

        $record = AttendanceRecord::where('employee_id', $employeeId)
            ->where('date', $date)->firstOrFail();

        $record->update(['clock_out' => $time]);
        return $record->fresh();
    }

    // --- Leave ---
    public function createLeaveRequest(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $leave = LeaveRequest::create($data);

            // Deduct from balance if approved leave type tracks balances
            if ($leave->status === 'approved') {
                $this->updateLeaveBalance($leave->employee_id, $leave->leave_type_id, $leave->start_date->year, $leave->days);
            }

            return $leave->fresh()->load(['employee', 'leaveType', 'approver']);
        });
    }

    public function approveLeave(LeaveRequest $leave, int $userId, ?string $reason = null): LeaveRequest
    {
        return DB::transaction(function () use ($leave, $userId, $reason) {
            $leave->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->updateLeaveBalance($leave->employee_id, $leave->leave_type_id, $leave->start_date->year, $leave->days);

            return $leave->fresh()->load(['employee', 'leaveType', 'approver']);
        });
    }

    public function rejectLeave(LeaveRequest $leave, string $reason): LeaveRequest
    {
        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
        return $leave->fresh();
    }

    public function cancelLeave(LeaveRequest $leave): LeaveRequest
    {
        return DB::transaction(function () use ($leave) {
            // Restore balance if it was approved
            if ($leave->status === 'approved') {
                $this->updateLeaveBalance($leave->employee_id, $leave->leave_type_id, $leave->start_date->year, -$leave->days);
            }

            $leave->update(['status' => 'cancelled']);
            return $leave->fresh();
        });
    }

    /**
     * Returns available leave types with remaining balance for an employee in a given year.
     */
    public function getLeaveBalances(int $employeeId, int $year): array
    {
        $types = LeaveType::all();
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->get()->keyBy('leave_type_id');

        return $types->map(function ($type) use ($balances, $year) {
            $bal = $balances->get($type->id);
            $allocated = $bal?->days_allocated ?? $type->days_per_year;
            $used = $bal?->days_used ?? 0;
            return [
                'leave_type_id' => $type->id,
                'leave_type' => $type->name,
                'year' => $year,
                'days_allocated' => $allocated,
                'days_used' => $used,
                'remaining' => $allocated - $used,
            ];
        })->toArray();
    }

    // --- Payroll ---
    public function generatePayslips(int $payPeriodId, int $userId): int
    {
        $period = PayPeriod::findOrFail($payPeriodId);
        $activeEmployees = Employee::where('employment_status', 'active')->get();
        $count = 0;

        foreach ($activeEmployees as $employee) {
            $existing = Payslip::where('employee_id', $employee->id)
                ->where('pay_period_id', $period->id)->first();

            if ($existing) continue;

            $daysInPeriod = $period->start_date->diffInDays($period->end_date) + 1;
            $daysInMonth = now()->daysInMonth;
            $basicPay = round(($employee->salary / $daysInMonth) * $daysInPeriod, 2);

            Payslip::create([
                'employee_id' => $employee->id,
                'pay_period_id' => $period->id,
                'currency_id' => $employee->salary_currency_id,
                'basic_pay' => $basicPay,
                'allowances' => [],
                'deductions' => [],
                'total_allowances' => 0,
                'total_deductions' => 0,
                'gross_pay' => $basicPay,
                'net_pay' => $basicPay,
                'status' => 'draft',
                'processed_by' => $userId,
            ]);

            $count++;
        }

        return $count;
    }

    public function approvePayslip(Payslip $payslip, array $data): Payslip
    {
        $payslip->update([
            'allowances' => $data['allowances'] ?? [],
            'deductions' => $data['deductions'] ?? [],
            'total_allowances' => $data['total_allowances'] ?? 0,
            'total_deductions' => $data['total_deductions'] ?? 0,
            'gross_pay' => $data['gross_pay'] ?? $payslip->basic_pay,
            'net_pay' => $data['net_pay'] ?? ($payslip->basic_pay + ($data['total_allowances'] ?? 0) - ($data['total_deductions'] ?? 0)),
            'status' => 'approved',
        ]);

        return $payslip->fresh()->load(['employee', 'payPeriod', 'currency']);
    }

    public function markPayslipPaid(Payslip $payslip): Payslip
    {
        $payslip->update(['status' => 'paid', 'paid_at' => now()]);
        return $payslip->fresh();
    }

    public function getHrStats(): array
    {
        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('employment_status', 'active')->count(),
            'departments' => Department::count(),
            'pending_leave' => LeaveRequest::where('status', 'pending')->count(),
            'attendance_today' => AttendanceRecord::where('date', now()->toDateString())->count(),
            'open_pay_period' => PayPeriod::where('status', 'open')->count(),
        ];
    }

    // --- Private Helpers ---

    private function seedLeaveBalances(Employee $employee): void
    {
        $year = now()->year;
        $types = LeaveType::all();

        foreach ($types as $type) {
            LeaveBalance::firstOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                ['days_allocated' => $type->days_per_year, 'days_used' => 0]
            );
        }
    }

    private function updateLeaveBalance(int $employeeId, int $leaveTypeId, int $year, float $days): void
    {
        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
            ['days_allocated' => LeaveType::find($leaveTypeId)?->days_per_year ?? 0, 'days_used' => 0]
        );

        $balance->increment('days_used', $days);
    }
}
