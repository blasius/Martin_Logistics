<?php

namespace App\Http\Controllers\Api\Fuel;

use App\Http\Controllers\Controller;
use App\Models\FuelDispense;
use App\Models\Vehicle;
use App\Models\Route;
use App\Services\FuelManagementService;
use Illuminate\Http\Request;

class FuelDispenseController extends Controller
{
    public function __construct(protected FuelManagementService $fuelService) {}

    public function index(Request $request)
    {
        $query = FuelDispense::with([
            'vehicle:id,plate_number,make,model',
            'driver:id',
            'driver.user:id,name',
            'tank:id,code,name',
            'dispenser:id,name',
        ]);

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->tank_id) {
            $query->where('tank_id', $request->tank_id);
        }
        if ($request->trip_id) {
            $query->where('trip_id', $request->trip_id);
        }
        if ($request->date_from) {
            $query->whereDate('dispensed_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('dispensed_at', '<=', $request->date_to);
        }

        return $query->orderByDesc('dispensed_at')->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'tank_id' => 'nullable|exists:fuel_tanks,id',
            'quantity' => 'required|numeric|min:0',
            'odometer_at_dispense' => 'nullable|numeric|min:0',
            'dispensed_at' => 'required|date',
            'trip_id' => 'nullable|exists:trips,id',
            'route_id' => 'nullable|exists:routes,id',
            'calculated_amount' => 'nullable|numeric|min:0',
            'override_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['dispensed_by'] = $request->user()->id;

        $dispense = $this->fuelService->dispense($validated);

        return $dispense->load([
            'vehicle:id,plate_number,make,model',
            'driver:id', 'driver.user:id,name',
            'tank:id,code,name', 'dispenser:id,name',
        ]);
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'current_fuel_level' => 'nullable|numeric|min:0',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $route = Route::findOrFail($validated['route_id']);

        return response()->json(
            $this->fuelService->calculateDispenseAmount(
                $vehicle,
                $route,
                $validated['current_fuel_level'] ?? null
            )
        );
    }

    public function show(FuelDispense $fuelDispense)
    {
        return $fuelDispense->load([
            'vehicle:id,plate_number,make,model',
            'driver:id', 'driver.user:id,name',
            'tank:id,code,name', 'dispenser:id,name',
            'trip', 'route',
        ]);
    }

    public function destroy(FuelDispense $fuelDispense)
    {
        if ($fuelDispense->tank_id) {
            $fuelDispense->tank->increment('current_level', $fuelDispense->quantity);
        }
        $fuelDispense->delete();
        return response()->json(['message' => 'Dispense record deleted']);
    }
}
