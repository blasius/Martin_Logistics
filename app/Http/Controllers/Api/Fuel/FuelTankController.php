<?php

namespace App\Http\Controllers\Api\Fuel;

use App\Http\Controllers\Controller;
use App\Services\FuelManagementService;
use App\Models\FuelTank;
use Illuminate\Http\Request;

class FuelTankController extends Controller
{
    public function __construct(protected FuelManagementService $fuelService) {}

    public function index()
    {
        return FuelTank::withCount('deliveries')->orderBy('code')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:fuel_tanks,code',
            'name' => 'required|string|max:255',
            'capacity' => 'required|numeric|min:0',
            'current_level' => 'required|numeric|min:0',
            'fuel_type' => 'required|in:diesel,petrol',
            'reorder_threshold' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        return FuelTank::create($validated);
    }

    public function show(FuelTank $fuelTank)
    {
        return $fuelTank->load(['deliveries.supplier:id,name', 'deliveries.receiver:id,name'])
            ->loadSum('deliveries', 'quantity')
            ->loadSum('dispenses', 'quantity');
    }

    public function update(Request $request, FuelTank $fuelTank)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:fuel_tanks,code,' . $fuelTank->id,
            'name' => 'required|string|max:255',
            'capacity' => 'required|numeric|min:0',
            'current_level' => 'required|numeric|min:0',
            'fuel_type' => 'required|in:diesel,petrol',
            'reorder_threshold' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'last_calibrated_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $fuelTank->update($validated);
        return $fuelTank;
    }

    public function destroy(FuelTank $fuelTank)
    {
        $fuelTank->delete();
        return response()->json(['message' => 'Tank deleted']);
    }

    public function updateLevel(Request $request, FuelTank $fuelTank)
    {
        $validated = $request->validate([
            'current_level' => 'required|numeric|min:0',
            'calibrated_at' => 'nullable|date',
        ]);

        $fuelTank->update([
            'current_level' => $validated['current_level'],
            'last_calibrated_at' => $validated['calibrated_at'] ?? now(),
        ]);

        return $fuelTank;
    }
}
