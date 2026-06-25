<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(protected AccountingService $accountingService) {}

    public function index(Request $request)
    {
        $query = JournalEntry::with(['createdBy:id,name', 'fiscalYear:id,name', 'lines']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('fiscal_year_id')) {
            $query->where('fiscal_year_id', $request->fiscal_year_id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function show(JournalEntry $journalEntry)
    {
        return $journalEntry->load([
            'lines.account',
            'createdBy:id,name',
            'fiscalYear:id,name',
            'reversedBy:id,name',
            'reversalEntry',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'date' => 'required|date',
            'type' => 'sometimes|in:manual,auto_invoice,auto_payment,auto_expense,auto_fuel,auto_payroll',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.notes' => 'nullable|string',
        ]);

        try {
            $entry = $this->accountingService->createEntry($validated, auth()->id());
            return $entry->load(['lines.account', 'createdBy:id,name', 'fiscalYear:id,name']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'date' => 'sometimes|date',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'lines' => 'sometimes|array|min:1',
            'lines.*.account_id' => 'required_with:lines|exists:chart_of_accounts,id',
            'lines.*.debit' => 'required_with:lines|numeric|min:0',
            'lines.*.credit' => 'required_with:lines|numeric|min:0',
            'lines.*.notes' => 'nullable|string',
        ]);

        try {
            $entry = $this->accountingService->updateEntry($journalEntry, $validated);
            return $entry->load(['lines.account', 'createdBy:id,name', 'fiscalYear:id,name']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }
    }

    public function destroy(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            return response()->json(['message' => 'Only draft entries can be deleted.'], 422);
        }
        $journalEntry->lines()->delete();
        $journalEntry->delete();
        return response()->json(['message' => 'Journal entry deleted']);
    }

    public function post(JournalEntry $journalEntry)
    {
        try {
            $entry = $this->accountingService->postEntry($journalEntry, auth()->id());
            return $entry->load(['lines.account', 'createdBy:id,name', 'fiscalYear:id,name']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $entry = $this->accountingService->reverseEntry($journalEntry, $validated['reason'], auth()->id());
            return $entry->load(['lines.account', 'createdBy:id,name', 'fiscalYear:id,name', 'reversalEntry']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
