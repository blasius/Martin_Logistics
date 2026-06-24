<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseManagementService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseManagementService $expenseService) {}

    public function index(Request $request)
    {
        $query = Expense::with([
            'expenseType:id,name',
            'vehicle:id,plate_number,make,model',
            'driver:id,name',
            'trip:id,reference',
            'route:id,name',
            'currency:id,code,symbol',
            'creator:id,name',
            'approvals.approver:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('expense_class')) {
            $query->where('expense_class', $request->expense_class);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('support_ticket_id')) {
            $query->where('support_ticket_id', $request->support_ticket_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $sortField = $request->sort_by ?? 'created_at';
        $sortDir = $request->sort_dir ?? 'desc';

        return $query->orderBy($sortField, $sortDir)->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'support_ticket_id' => 'nullable|exists:support_tickets,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:users,id',
            'trip_id' => 'nullable|exists:trips,id',
            'route_id' => 'nullable|exists:routes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'location' => 'nullable|string|max:255',
            'odometer' => 'nullable|integer|min:0',
            'expense_class' => 'required|in:fixed,variable',
        ]);

        $proof = $request->file('proof_of_payment');

        $expense = $this->expenseService->createExpense($validated, $request->user()->id, $proof);

        return response()->json($expense, 201);
    }

    public function show(Expense $expense)
    {
        return $expense->load([
            'expenseType',
            'supportTicket',
            'vehicle:id,plate_number,make,model',
            'driver:id,name',
            'trip:id,reference',
            'route:id,name',
            'currency:id,code,symbol',
            'creator:id,name',
            'approvals.approver:id,name',
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        abort_if(in_array($expense->status, ['paid', 'rejected']),
            422, 'Cannot modify a paid or rejected expense.');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'amount' => 'sometimes|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'location' => 'nullable|string|max:255',
            'odometer' => 'nullable|integer|min:0',
        ]);

        $expense->update($validated);

        return $expense->fresh()->load([
            'expenseType',
            'vehicle:id,plate_number,make,model',
            'currency:id,code,symbol',
            'creator:id,name',
        ]);
    }

    public function destroy(Expense $expense)
    {
        abort_if($expense->status === 'paid', 422, 'Cannot delete a paid expense.');
        $expense->delete();
        return response()->json(['message' => 'Expense deleted']);
    }

    public function approve(int $id, Request $request)
    {
        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        $expense = $this->expenseService->approve($expense, $request->user()->id, $validated['comment'] ?? null);

        return $expense;
    }

    public function reject(int $id, Request $request)
    {
        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $expense = $this->expenseService->reject($expense, $request->user()->id, $validated['reason']);

        return $expense;
    }

    public function pay(int $id, Request $request)
    {
        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
            'proof_of_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $proof = $request->file('proof_of_payment');

        $expense = $this->expenseService->recordPayment($expense, $validated, $proof);

        return $expense;
    }

    public function convertFromTicket(int $ticketId, Request $request)
    {
        $validated = $request->validate([
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:users,id',
            'trip_id' => 'nullable|exists:trips,id',
            'route_id' => 'nullable|exists:routes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'location' => 'nullable|string|max:255',
            'odometer' => 'nullable|integer|min:0',
            'expense_class' => 'nullable|in:fixed,variable',
        ]);

        $expense = $this->expenseService->convertFromTicket($ticketId, $validated, $request->user()->id);

        return response()->json($expense, 201);
    }

    public function dashboard()
    {
        return response()->json($this->expenseService->dashboardStats());
    }

    public function reportByVehicle(Request $request)
    {
        $query = Expense::selectRaw('vehicle_id, COUNT(*) as count, SUM(amount) as total')
            ->with('vehicle:id,plate_number,make,model')
            ->groupBy('vehicle_id');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return $query->orderByDesc('total')->paginate($request->per_page ?? 50);
    }

    public function reportByCategory(Request $request)
    {
        $query = Expense::selectRaw('category, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('category');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return $query->orderByDesc('total')->get();
    }
}
