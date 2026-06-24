<?php

namespace App\Http\Controllers\Api\Fuel;

use App\Http\Controllers\Controller;
use App\Models\FuelTank;
use App\Models\FuelDispense;
use App\Models\VehicleRouteFuelRatio;
use App\Services\FuelManagementService;
use Illuminate\Http\Request;

class FuelDashboardController extends Controller
{
    public function __construct(protected FuelManagementService $fuelService) {}

    public function index()
    {
        return response()->json($this->fuelService->dashboardStats());
    }

    public function ratios(Request $request)
    {
        $query = VehicleRouteFuelRatio::with(['vehicle:id,plate_number,make,model', 'route:id,name']);

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        return $query->orderByDesc('created_at')->paginate($request->per_page ?? 50);
    }

    public function storeRatio(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'km_per_liter' => 'required|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'notes' => 'nullable|string',
        ]);

        return VehicleRouteFuelRatio::create($validated);
    }

    public function deleteRatio(VehicleRouteFuelRatio $vehicleRouteFuelRatio)
    {
        $vehicleRouteFuelRatio->delete();
        return response()->json(['message' => 'Ratio deleted']);
    }
}
