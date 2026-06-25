<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        $query = ChartOfAccount::with(['parent:id,name,code', 'children']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('code')->paginate($request->per_page ?? 100);
    }

    public function show(ChartOfAccount $chartOfAccount)
    {
        return $chartOfAccount->load(['parent:id,name,code', 'children']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        $exists = ChartOfAccount::where('code', $validated['code'])
            ->where('type', $validated['type'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Account code already exists for this type.'], 422);
        }

        return ChartOfAccount::create($validated);
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:20',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['code'])) {
            $exists = ChartOfAccount::where('code', $validated['code'])
                ->where('type', $validated['type'] ?? $chartOfAccount->type)
                ->where('id', '!=', $chartOfAccount->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Account code already exists for this type.'], 422);
            }
        }

        $chartOfAccount->update($validated);
        return $chartOfAccount->fresh();
    }

    public function destroy(ChartOfAccount $chartOfAccount)
    {
        if ($chartOfAccount->children()->exists()) {
            return response()->json(['message' => 'Cannot delete account with sub-accounts.'], 422);
        }
        if ($chartOfAccount->journalLines()->exists()) {
            return response()->json(['message' => 'Cannot delete account with journal entries.'], 422);
        }
        $chartOfAccount->delete();
        return response()->json(['message' => 'Account deleted']);
    }
}
