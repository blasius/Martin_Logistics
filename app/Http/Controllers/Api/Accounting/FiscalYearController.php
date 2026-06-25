<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function index(Request $request)
    {
        return FiscalYear::orderBy('start_date', 'desc')->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $overlap = FiscalYear::where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();
        if ($overlap) {
            return response()->json(['message' => 'Fiscal year overlaps with an existing one.'], 422);
        }

        return FiscalYear::create($validated);
    }

    public function show(FiscalYear $fiscalYear)
    {
        return $fiscalYear;
    }

    public function update(Request $request, FiscalYear $fiscalYear)
    {
        if ($fiscalYear->is_closed) {
            return response()->json(['message' => 'Cannot update a closed fiscal year.'], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_closed' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $fiscalYear->update($validated);
        return $fiscalYear->fresh();
    }

    public function destroy(FiscalYear $fiscalYear)
    {
        if ($fiscalYear->journalEntries()->exists()) {
            return response()->json(['message' => 'Cannot delete a fiscal year with journal entries.'], 422);
        }
        $fiscalYear->delete();
        return response()->json(['message' => 'Fiscal year deleted']);
    }
}
