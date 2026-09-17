<?php

namespace App\Http\Controllers\Api\Fuel;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\FuelManagementService;
use Illuminate\Http\Request;

class FuelReportController extends Controller
{
    public function __construct(protected FuelManagementService $fuelService) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'flag' => 'nullable|in:normal,caution,excessive',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return response()->json($this->fuelService->tripFuelReports($validated));
    }

    public function show(Trip $trip)
    {
        return response()->json($this->fuelService->tripFuelReport($trip->id));
    }
}