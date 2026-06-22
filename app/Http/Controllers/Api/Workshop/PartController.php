<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Http\Request;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $query = Part::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'parts' => $query->orderBy('name')->paginate($request->per_page ?? 15),
            'categories' => Part::select('category')->distinct()->whereNotNull('category')->pluck('category'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:parts,sku',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_price' => 'nullable|numeric|min:0',
            'compatible_vehicle_makes' => 'nullable|string',
        ]);

        return Part::create($validated);
    }

    public function show(Part $part)
    {
        return $part->load(['stockLevels.warehouse']);
    }

    public function update(Request $request, Part $part)
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:parts,sku,' . $part->id,
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_price' => 'nullable|numeric|min:0',
            'compatible_vehicle_makes' => 'nullable|string',
        ]);

        $part->update($validated);

        return $part;
    }

    public function destroy(Part $part)
    {
        $part->delete();

        return response()->json(['message' => 'Part deleted']);
    }
}
