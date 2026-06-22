<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        return response()->json([
            'warehouses' => $query->orderBy('name')->paginate($request->per_page ?? 15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:warehouses,code',
            'location' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return Warehouse::create($validated);
    }

    public function show(Warehouse $warehouse)
    {
        return $warehouse->load(['stockLevels.part']);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:warehouses,code,' . $warehouse->id,
            'location' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($validated);

        return $warehouse;
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json(['message' => 'Warehouse deleted']);
    }

    public function list()
    {
        return Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }
}
