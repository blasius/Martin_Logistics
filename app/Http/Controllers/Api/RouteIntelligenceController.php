<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripFuelAnalysis;
use App\Services\RouteIntelligenceService;
use Illuminate\Http\Request;

class RouteIntelligenceController extends Controller
{
    public function __construct(protected RouteIntelligenceService $intelligenceService) {}

    public function dashboard()
    {
        return response()->json($this->intelligenceService->dashboardStats());
    }

    public function checkDeviation(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
        ]);

        $trip = Trip::with('vehicle.snapshot', 'route')->findOrFail($validated['trip_id']);
        $log = $this->intelligenceService->checkDeviation($trip);

        return response()->json([
            'is_deviated' => (bool) $trip->fresh()->is_deviated,
            'deviation_log' => $log,
        ]);
    }

    public function batchCheckDeviation()
    {
        $trips = Trip::where('status', 'on_route')
            ->whereNotNull('route_id')
            ->with('vehicle.snapshot', 'route')
            ->get();

        $results = [];
        foreach ($trips as $trip) {
            $log = $this->intelligenceService->checkDeviation($trip);
            if ($log) {
                $results[] = [
                    'trip_id' => $trip->id,
                    'vehicle_plate' => $trip->vehicle?->plate_number,
                    'distance_meters' => $log->distance_from_route_meters,
                ];
            }
        }

        return response()->json([
            'checked' => $trips->count(),
            'deviations_found' => count($results),
            'deviations' => $results,
        ]);
    }

    public function createAutoTicket(Request $request)
    {
        $validated = $request->validate([
            'trip_fuel_analysis_id' => 'required|exists:trip_fuel_analyses,id',
        ]);

        $analysis = TripFuelAnalysis::with('trip', 'vehicle')->findOrFail($validated['trip_fuel_analysis_id']);

        if ($analysis->flag !== 'excessive') {
            return response()->json(['message' => 'Fuel analysis is not flagged as excessive.'], 422);
        }

        if ($analysis->trip?->auto_ticket_id) {
            $existing = \App\Models\SupportTicket::find($analysis->trip->auto_ticket_id);
            if ($existing) {
                return response()->json($existing->load('category'));
            }
        }

        $ticket = $this->intelligenceService->createExcessiveFuelTicket($analysis);

        return response()->json($ticket->load('category'), 201);
    }
}
