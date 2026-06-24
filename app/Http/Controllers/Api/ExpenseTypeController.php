<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseType;
use App\Services\ExpenseManagementService;
use Illuminate\Http\Request;

class ExpenseTypeController extends Controller
{
    public function __construct(protected ExpenseManagementService $expenseService) {}

    public function index(Request $request)
    {
        $query = ExpenseType::with(['currency:id,code,symbol', 'creator:id,name', 'approver:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'expense_class' => 'required|in:fixed,variable',
            'default_amount' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
        ]);

        $type = $this->expenseService->createExpenseType($validated, $request->user()->id);

        return $type->load(['currency:id,code,symbol', 'creator:id,name']);
    }

    public function show(ExpenseType $expenseType)
    {
        return $expenseType->load([
            'currency:id,code,symbol',
            'creator:id,name',
            'approver:id,name',
        ]);
    }

    public function update(Request $request, ExpenseType $expenseType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'expense_class' => 'sometimes|in:fixed,variable',
            'default_amount' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $expenseType->update($validated);

        return $expenseType->fresh()->load([
            'currency:id,code,symbol',
            'creator:id,name',
            'approver:id,name',
        ]);
    }

    public function destroy(ExpenseType $expenseType)
    {
        $expenseType->delete();
        return response()->json(['message' => 'Expense type deleted']);
    }

    public function submit(int $id, Request $request)
    {
        $type = $this->expenseService->submitExpenseType($id, $request->user()->id);
        return $type->load(['currency:id,code,symbol', 'creator:id,name']);
    }

    public function approveType(int $id, Request $request)
    {
        $type = $this->expenseService->approveExpenseType($id, $request->user()->id);
        return $type->load(['currency:id,code,symbol', 'approver:id,name']);
    }

    public function rejectType(int $id, Request $request)
    {
        $type = $this->expenseService->rejectExpenseType($id, $request->user()->id);
        return $type;
    }

    public function categories()
    {
        return response()->json([
            'tires', 'engine', 'brakes', 'electrical', 'body',
            'tolls', 'permits', 'accommodation', 'meals',
            'fuel_external', 'towing', 'other',
        ]);
    }
}
