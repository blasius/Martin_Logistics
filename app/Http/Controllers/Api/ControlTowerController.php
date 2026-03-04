<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleSnapshot;
use App\Models\TelemetryEvent;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ControlTowerController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Total Fleet Count
        $totalUnits = Vehicle::count();

        // 2. Fetch Snapshots with Driver Names via the pivot table
        // Path: Snapshot -> Vehicle -> driver_vehicle_assignments (where end_date is null) -> Users
        $snapshots = VehicleSnapshot::join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('driver_vehicle_assignments', function($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->whereNull('driver_vehicle_assignments.end_date');
            })
            ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
            ->select(
                'vehicles.plate_number as plate',
                'users.name as driver_name',
                'vehicle_snapshots.*'
            )
            ->get();

        // 3. Aggregate Today's Fuel Stats
        $dailyFuel = TelemetryEvent::whereDate('occurred_at', $today)
            ->selectRaw("SUM(CASE WHEN type = 'fuel_refill' THEN value ELSE 0 END) as total_refilled")
            ->selectRaw("SUM(CASE WHEN type = 'fuel_drain' THEN value ELSE 0 END) as total_stolen")
            ->first();

        // 4. Fetch Lists for Dashboard Cards
        $recentThefts = TelemetryEvent::where('type', 'fuel_drain')
            ->join('vehicles', 'telemetry_events.vehicle_id', '=', 'vehicles.id')
            ->orderBy('occurred_at', 'desc')
            ->take(5)
            ->get(['vehicles.plate_number as plate', 'telemetry_events.value as val']);

        $recentFillings = TelemetryEvent::where('type', 'fuel_refill')
            ->join('vehicles', 'telemetry_events.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('driver_vehicle_assignments', function($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->whereNull('driver_vehicle_assignments.end_date');
            })
            ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
            ->orderBy('occurred_at', 'desc')
            ->take(5)
            ->get(['vehicles.plate_number as plate', 'users.name as driver_name', 'telemetry_events.value as val']);

        return response()->json([
            'totalUnits' => $totalUnits,
            'stats' => [
                'totalRefilled' => (float) ($dailyFuel->total_refilled ?? 0),
                'totalStolen'   => (float) ($dailyFuel->total_stolen ?? 0),

                'criticalFuel' => $snapshots->where('low_fuel', true)->map(fn($s) => [
                    'id'    => $s->vehicle_id,
                    'plate' => $s->plate,
                    'val'   => $s->fuel_level
                ])->values(),

                'thefts'   => $recentThefts,
                'fillings' => $recentFillings,

                'breakdowns' => TelemetryEvent::where('type', 'breakdown')
                    ->whereDate('occurred_at', $today)
                    ->get(),

                'longStops' => $snapshots->filter(function($s) {
                    // Logic for vehicles that haven't sent data in 24h
                    return $s->last_seen_at < Carbon::now()->subHours(24);
                })->values(),
            ]
        ]);
    }

    public function report(Request $request, $type)
    {
        $start = $request->input('start');
        $end   = $request->input('end');

        // Base query using the current assignment logic
        $query = TelemetryEvent::join('vehicles', 'telemetry_events.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('driver_vehicle_assignments', function($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->whereNull('driver_vehicle_assignments.end_date');
            })
            ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
            ->select([
                'vehicles.plate_number',
                'users.name as driver_name',
                'telemetry_events.value as val',
                'telemetry_events.occurred_at as time'
            ]);

        if ($type === 'thefts') {
            $query->where('type', 'fuel_drain');
        } elseif ($type === 'fillings') {
            $query->where('type', 'fuel_refill');
        } elseif ($type === 'breakdowns') {
            $query->where('type', 'breakdown');
        }

        if ($start && $end) {
            $query->whereBetween('occurred_at', [$start, $end]);
        }

        // Special handling for the Critical Fuel modal
        if ($type === 'critical-fuel') {
            return VehicleSnapshot::where('low_fuel', true)
                ->join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
                ->leftJoin('driver_vehicle_assignments', function($join) {
                    $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                        ->whereNull('driver_vehicle_assignments.end_date');
                })
                ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
                ->get([
                    'vehicles.plate_number',
                    'users.name as driver_name',
                    'vehicle_snapshots.fuel_level as val',
                    'vehicle_snapshots.last_seen_at as time'
                ]);
        }

        return response()->json($query->orderBy('occurred_at', 'desc')->get());
    }
}
