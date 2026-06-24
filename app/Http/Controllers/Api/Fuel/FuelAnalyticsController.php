<?php

namespace App\Http\Controllers\Api\Fuel;

use App\Http\Controllers\Controller;
use App\Services\FuelManagementService;
use App\Services\RouteIntelligenceService;
use App\Models\TripFuelAnalysis;
use App\Models\DriverFuelRating;
use Illuminate\Http\Request;

class FuelAnalyticsController extends Controller
{
    public function __construct(
        protected FuelManagementService $fuelService,
        protected RouteIntelligenceService $intelligenceService,
    ) {}

    public function tripAnalysis(TripFuelAnalysis $tripFuelAnalysis)
    {
        return $tripFuelAnalysis->load(['trip', 'vehicle:id,plate_number,make,model', 'route']);
    }

    public function analyseTrip(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'auto_ticket' => 'nullable|boolean',
        ]);

        $analysis = $this->fuelService->analyseTrip($validated['trip_id']);

        if (!empty($validated['auto_ticket']) && $analysis->flag === 'excessive') {
            $analysis->load('trip', 'vehicle');
            if (!$analysis->trip?->auto_ticket_id) {
                $this->intelligenceService->createExcessiveFuelTicket($analysis);
            }
        }

        return $analysis->load('trip', 'vehicle:id,plate_number,make,model', 'route');
    }

    public function driverRating(DriverFuelRating $driverFuelRating)
    {
        return $driverFuelRating->load('driver.user:id,name');
    }

    public function rateDriver(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        return $this->fuelService->rateDriver(
            $validated['driver_id'],
            $validated['period_start'],
            $validated['period_end']
        );
    }

    public function consumptionReport(Request $request)
    {
        $query = TripFuelAnalysis::with(['vehicle:id,plate_number,make,model', 'trip', 'route']);

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->flag) {
            $query->where('flag', $request->flag);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query->orderByDesc('created_at')->paginate($request->per_page ?? 20);
    }

    public function driverRankings(Request $request)
    {
        $query = DriverFuelRating::with('driver.user:id,name');

        if ($request->rating) {
            $query->where('rating', $request->rating);
        }

        return $query->orderBy('avg_variance_percent', 'desc')->paginate($request->per_page ?? 20);
    }

    public function pumpToTankVariance(Request $request)
    {
        $validated = $request->validate([
            'tank_id' => 'nullable|exists:fuel_tanks,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        return response()->json(
            $this->fuelService->pumpToTankVariance(
                $validated['tank_id'] ?? null,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            )
        );
    }
}
