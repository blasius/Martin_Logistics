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

        // 4. Fetch per-vehicle totals for Dashboard Cards
        $recentThefts = $this->aggregateFuelEventsForToday('fuel_drain');
        $recentFillings = $this->aggregateFuelEventsForToday('fuel_refill');

        return response()->json([
            'totalUnits' => $totalUnits,
            'stats' => [
                'totalRefilled' => (float) ($dailyFuel->total_refilled ?? 0),
                'totalStolen'   => (float) ($dailyFuel->total_stolen ?? 0),

                'criticalFuel' => $snapshots->where('low_fuel', true)
                    ->where('fuel_level', '>', 0)
                    ->map(fn($s) => [
                    'id'    => $s->vehicle_id,
                    'plate' => $s->plate,
                    'val'   => $s->fuel_level
                ])->values(),

                'thefts'   => $recentThefts,
                'fillings' => $recentFillings,

                'breakdowns' => Vehicle::where('status', 'inactive')
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
        $start = $request->input('start', Carbon::today()->startOfDay()->toDateTimeString());
        $end = $request->input('end', Carbon::today()->endOfDay()->toDateTimeString());
        $search = trim((string) $request->input('search', ''));

        if ($type === 'critical-fuel') {
            return VehicleSnapshot::where('low_fuel', true)
                ->where('fuel_level', '>', 0)
                ->join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
                ->leftJoin('driver_vehicle_assignments', function($join) {
                    $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                        ->whereNull('driver_vehicle_assignments.end_date');
                })
                ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('vehicles.plate_number', 'like', "%{$search}%")
                            ->orWhere('users.name', 'like', "%{$search}%");
                    });
                })
                ->orderBy('vehicle_snapshots.fuel_level')
                ->get([
                    'vehicles.plate_number',
                    'users.name as driver_name',
                    'vehicle_snapshots.fuel_level as val',
                    'vehicle_snapshots.last_seen_at as time'
                ]);
        }

        if ($type === 'breakdowns') {
            return Vehicle::where('status', 'inactive')
                ->when($search !== '', fn ($query) => $query->where('plate_number', 'like', "%{$search}%"))
                ->orderBy('plate_number')
                ->get([
                    'plate_number',
                    DB::raw("'Unassigned' as driver_name"),
                    DB::raw("'Inactive' as val"),
                    'updated_at as time'
                ]);
        }

        if ($type === 'long-stops') {
            return VehicleSnapshot::join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
                ->leftJoin('driver_vehicle_assignments', function($join) {
                    $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                        ->whereNull('driver_vehicle_assignments.end_date');
                })
                ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
                ->where('vehicle_snapshots.last_seen_at', '<', Carbon::now()->subHours(24))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('vehicles.plate_number', 'like', "%{$search}%")
                            ->orWhere('users.name', 'like', "%{$search}%");
                    });
                })
                ->orderBy('vehicle_snapshots.last_seen_at')
                ->get([
                    'vehicles.plate_number',
                    'users.name as driver_name',
                    DB::raw("'24h+ stationary' as val"),
                    'vehicle_snapshots.last_seen_at as time'
                ]);
        }

        // Base query using the current assignment logic
        $query = TelemetryEvent::join('vehicles', 'telemetry_events.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('driver_vehicle_assignments', function($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->whereNull('driver_vehicle_assignments.end_date');
            })
            ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
            ->select([
                'telemetry_events.id',
                'vehicles.plate_number',
                'users.name as driver_name',
                'telemetry_events.value as val',
                'telemetry_events.occurred_at as time'
            ]);

        if ($type === 'thefts') {
            $query->where('type', 'fuel_drain');
        } elseif ($type === 'fillings') {
            $query->where('type', 'fuel_refill');
        } else {
            return response()->json([]);
        }

        if (in_array($type, ['thefts', 'fillings'], true)) {
            $query->whereBetween('occurred_at', [$start, $end]);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('vehicles.plate_number', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('occurred_at', 'desc')->get());
    }

    private function aggregateFuelEventsForToday(string $type)
    {
        return TelemetryEvent::where('type', $type)
            ->whereDate('occurred_at', Carbon::today())
            ->join('vehicles', 'telemetry_events.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('driver_vehicle_assignments', function($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->whereNull('driver_vehicle_assignments.end_date');
            })
            ->leftJoin('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
            ->groupBy('telemetry_events.vehicle_id', 'vehicles.plate_number', 'users.name')
            ->orderByDesc(DB::raw('SUM(telemetry_events.value)'))
            ->get([
                'telemetry_events.vehicle_id as id',
                'vehicles.plate_number as plate',
                'users.name as driver_name',
                DB::raw('SUM(telemetry_events.value) as val')
            ]);
    }
}
