<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Models\Employee;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Services\HrService;
use Illuminate\Http\Request;

class HrController extends Controller
{
    public function __construct(protected HrService $hrService) {}

    // === Departments ===
    public function indexDepartments()
    {
        return Department::withCount('employees')->latest()->get();
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255|unique:departments', 'description' => 'nullable|string']);
        return response()->json($this->hrService->createDepartment($validated), 201);
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $validated = $request->validate(['name' => 'sometimes|string|max:255|unique:departments,name,' . $department->id, 'description' => 'nullable|string', 'is_active' => 'boolean']);
        return response()->json($this->hrService->updateDepartment($department, $validated));
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->employees()->exists()) {
            return response()->json(['message' => 'Cannot delete department with active employees'], 422);
        }
        $department->delete();
        return response()->json(null, 204);
    }

    // === Positions ===
    public function indexPositions(Request $request)
    {
        $query = Position::with(['department:id,name']);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        return $query->latest()->get();
    }

    public function storePosition(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        return response()->json($this->hrService->createPosition($validated), 201);
    }

    public function updatePosition(Request $request, Position $position)
    {
        $validated = $request->validate([
            'department_id' => 'sometimes|exists:departments,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        return response()->json($this->hrService->updatePosition($position, $validated));
    }

    public function destroyPosition(Position $position)
    {
        if ($position->employees()->exists()) {
            return response()->json(['message' => 'Cannot delete position with assigned employees'], 422);
        }
        $position->delete();
        return response()->json(null, 204);
    }

    // === Employees ===
    public function indexEmployees(Request $request)
    {
        $query = Employee::with(['department:id,name', 'position:id,title', 'salaryCurrency:id,code', 'user:id,name']);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('status')) $query->where('employment_status', $request->status);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('employee_number', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            });
        }
        return $query->latest()->paginate($request->per_page ?? 50);
    }

    public function storeEmployee(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'hire_date' => 'required|date',
            'employment_status' => 'sometimes|string|in:active,suspended,terminated,resigned',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'salary' => 'sometimes|numeric|min:0',
            'salary_currency_id' => 'nullable|exists:currencies,id',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:100',
            'bank_code' => 'nullable|string|max:50',
        ]);
        $validated['created_by'] = auth()->id();
        return response()->json($this->hrService->createEmployee($validated), 201);
    }

    public function showEmployee(Employee $employee)
    {
        return $employee->load([
            'department:id,name', 'position:id,title,department_id', 'position.department:id,name',
            'salaryCurrency:id,code', 'user:id,name,email',
            'creator:id,name', 'attendanceRecords' => fn($q) => $q->latest('date')->limit(30),
            'leaveRequests' => fn($q) => $q->with('leaveType:id,name')->latest()->limit(10),
        ]);
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'hire_date' => 'sometimes|date',
            'employment_status' => 'sometimes|string|in:active,suspended,terminated,resigned',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'salary' => 'sometimes|numeric|min:0',
            'salary_currency_id' => 'nullable|exists:currencies,id',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:100',
            'bank_code' => 'nullable|string|max:50',
        ]);
        return response()->json($this->hrService->updateEmployee($employee, $validated));
    }

    // === Attendance ===
    public function indexAttendance(Request $request)
    {
        $query = AttendanceRecord::with('employee:id,first_name,last_name,employee_number');
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('date')) $query->where('date', $request->date);
        if ($request->filled('from')) $query->where('date', '>=', $request->from);
        if ($request->filled('to')) $query->where('date', '<=', $request->to);
        return $query->latest('date')->paginate($request->per_page ?? 50);
    }

    public function clockIn(Request $request)
    {
        $validated = $request->validate(['employee_id' => 'required|exists:employees,id', 'time' => 'nullable|date_format:H:i:s']);
        return response()->json($this->hrService->clockIn($validated['employee_id'], $validated['time'] ?? null));
    }

    public function clockOut(Request $request)
    {
        $validated = $request->validate(['employee_id' => 'required|exists:employees,id', 'time' => 'nullable|date_format:H:i:s']);
        return response()->json($this->hrService->clockOut($validated['employee_id'], $validated['time'] ?? null));
    }

    // === Leave Types ===
    public function indexLeaveTypes()
    {
        return LeaveType::withCount('leaveRequests')->latest()->get();
    }

    public function storeLeaveType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types',
            'days_per_year' => 'required|integer|min:0',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
        ]);
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        return response()->json(LeaveType::create($validated), 201);
    }

    // === Leave Requests ===
    public function indexLeaveRequests(Request $request)
    {
        $query = LeaveRequest::with(['employee:id,first_name,last_name,employee_number', 'leaveType:id,name', 'approver:id,name']);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        return $query->latest()->paginate($request->per_page ?? 50);
    }

    public function storeLeaveRequest(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'status' => 'sometimes|string|in:pending',
        ]);
        $validated['days'] = now()->parse($validated['start_date'])->diffInDays(now()->parse($validated['end_date'])) + 1;
        $validated['created_by'] = auth()->id();
        return response()->json($this->hrService->createLeaveRequest($validated), 201);
    }

    public function approveLeave(LeaveRequest $leaveRequest)
    {
        return response()->json($this->hrService->approveLeave($leaveRequest, auth()->id()));
    }

    public function rejectLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate(['reason' => 'required|string']);
        return response()->json($this->hrService->rejectLeave($leaveRequest, $validated['reason']));
    }

    public function cancelLeave(LeaveRequest $leaveRequest)
    {
        return response()->json($this->hrService->cancelLeave($leaveRequest));
    }

    public function leaveBalances(Request $request)
    {
        $validated = $request->validate(['employee_id' => 'required|exists:employees,id', 'year' => 'sometimes|integer|min:2000']);
        return response()->json($this->hrService->getLeaveBalances($validated['employee_id'], $validated['year'] ?? now()->year));
    }

    // === Pay Periods ===
    public function indexPayPeriods()
    {
        return PayPeriod::withCount('payslips')->latest()->get();
    }

    public function storePayPeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        return response()->json(PayPeriod::create($validated), 201);
    }

    public function closePayPeriod(PayPeriod $payPeriod)
    {
        $payPeriod->update(['status' => 'closed']);
        return response()->json($payPeriod->fresh());
    }

    // === Payslips ===
    public function indexPayslips(Request $request)
    {
        $query = Payslip::with(['employee:id,first_name,last_name,employee_number', 'payPeriod:id,name', 'currency:id,code']);
        if ($request->filled('pay_period_id')) $query->where('pay_period_id', $request->pay_period_id);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        return $query->latest()->paginate($request->per_page ?? 50);
    }

    public function generatePayslips(Request $request)
    {
        $validated = $request->validate(['pay_period_id' => 'required|exists:pay_periods,id']);
        $count = $this->hrService->generatePayslips($validated['pay_period_id'], auth()->id());
        return response()->json(['message' => "{$count} payslips generated", 'count' => $count]);
    }

    public function approvePayslip(Request $request, Payslip $payslip)
    {
        $validated = $request->validate([
            'allowances' => 'nullable|array',
            'deductions' => 'nullable|array',
            'total_allowances' => 'nullable|numeric|min:0',
            'total_deductions' => 'nullable|numeric|min:0',
            'gross_pay' => 'nullable|numeric|min:0',
            'net_pay' => 'nullable|numeric|min:0',
        ]);
        return response()->json($this->hrService->approvePayslip($payslip, $validated));
    }

    public function markPayslipPaid(Payslip $payslip)
    {
        return response()->json($this->hrService->markPayslipPaid($payslip));
    }

    // === Stats ===
    public function stats()
    {
        return response()->json($this->hrService->getHrStats());
    }
}
