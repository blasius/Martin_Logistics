<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportCategory;
use Illuminate\Http\Request;

class SupportCategoryController extends Controller
{
    public function index(Request $request)
    {
        return SupportCategory::query()
            ->when($request->boolean('all'), fn($q) => $q, fn($q) => $q->where('is_active', true))
            ->withCount('tickets')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:support_categories,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return SupportCategory::create($validated);
    }

    public function update(Request $request, SupportCategory $supportCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:support_categories,name,' . $supportCategory->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supportCategory->update($validated);

        return $supportCategory;
    }

    public function destroy(SupportCategory $supportCategory)
    {
        if ($supportCategory->tickets()->exists()) {
            return response()->json(['message' => 'Cannot delete category with active tickets.'], 422);
        }

        $supportCategory->delete();

        return response()->noContent();
    }
}
