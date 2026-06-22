<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = StockLevel::with(['warehouse:id,name', 'part:id,name,sku,category']);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->part_id) {
            $query->where('part_id', $request->part_id);
        }

        if ($request->low_stock) {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        }

        return response()->json([
            'stock_levels' => $query->orderBy('warehouse_id')->paginate($request->per_page ?? 15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'part_id' => 'required|exists:parts,id',
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'required|numeric|min:0',
        ]);

        $exists = StockLevel::where('warehouse_id', $validated['warehouse_id'])
            ->where('part_id', $validated['part_id'])
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Stock level already exists for this part/warehouse'], 409);
        }

        $stockLevel = StockLevel::create($validated);

        StockMovement::create([
            'warehouse_id' => $validated['warehouse_id'],
            'part_id' => $validated['part_id'],
            'quantity' => $validated['quantity'],
            'type' => 'in',
            'user_id' => $request->user()->id,
            'notes' => 'Initial stock entry',
        ]);

        return $stockLevel->load(['warehouse:id,name', 'part:id,name,sku,category']);
    }

    public function update(Request $request, StockLevel $stockLevel)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'required|numeric|min:0',
        ]);

        $stockLevel->update($validated);

        return $stockLevel->load(['warehouse:id,name', 'part:id,name,sku']);
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'part_id' => 'required|exists:parts,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:in,out,adjust',
            'notes' => 'nullable|string',
        ]);

        $stockLevel = StockLevel::firstOrCreate(
            ['warehouse_id' => $validated['warehouse_id'], 'part_id' => $validated['part_id']],
            ['quantity' => 0, 'min_quantity' => 0]
        );

        if ($validated['type'] === 'in') {
            $stockLevel->increment('quantity', $validated['quantity']);
        } elseif ($validated['type'] === 'out') {
            $stockLevel->decrement('quantity', $validated['quantity']);
        } else {
            $stockLevel->update(['quantity' => $validated['quantity']]);
        }

        StockMovement::create([
            'warehouse_id' => $validated['warehouse_id'],
            'part_id' => $validated['part_id'],
            'quantity' => $validated['quantity'],
            'type' => $validated['type'],
            'user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? ($validated['type'] === 'in' ? 'Stock added' : ($validated['type'] === 'out' ? 'Stock removed' : 'Stock adjusted')),
        ]);

        return $stockLevel->load(['warehouse:id,name', 'part:id,name,sku']);
    }
}
