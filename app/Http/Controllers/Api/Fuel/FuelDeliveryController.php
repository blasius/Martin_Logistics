<?php

namespace App\Http\Controllers\Api\Fuel;

use App\Http\Controllers\Controller;
use App\Models\FuelDelivery;
use App\Models\FuelTank;
use Illuminate\Http\Request;

class FuelDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = FuelDelivery::with(['tank:id,code,name', 'supplier:id,name', 'receiver:id,name']);

        if ($request->tank_id) {
            $query->where('tank_id', $request->tank_id);
        }

        return $query->orderByDesc('delivered_at')->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tank_id' => 'required|exists:fuel_tanks,id',
            'supplier_id' => 'required|exists:vendors,id',
            'fuel_type' => 'required|in:diesel,petrol',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'invoice_reference' => 'nullable|string|max:255',
            'delivered_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $validated['received_by'] = $request->user()->id;

        $delivery = FuelDelivery::create($validated);

        FuelTank::where('id', $validated['tank_id'])->increment('current_level', $validated['quantity']);

        return $delivery->load(['tank:id,code,name', 'supplier:id,name', 'receiver:id,name']);
    }

    public function show(FuelDelivery $fuelDelivery)
    {
        return $fuelDelivery->load(['tank:id,code,name', 'supplier:id,name', 'receiver:id,name']);
    }

    public function destroy(FuelDelivery $fuelDelivery)
    {
        FuelTank::where('id', $fuelDelivery->tank_id)->decrement('current_level', $fuelDelivery->quantity);
        $fuelDelivery->delete();
        return response()->json(['message' => 'Delivery deleted']);
    }
}
