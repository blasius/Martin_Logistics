<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FleetDashboardController extends Controller
{
    /**
     * Fetches the 24h Telemetry Snapshot for the Fleet Command Dashboard.
     * Optimized for "Today" view with aggregated fuel metrics.
     */
    public function getSnapshot()
    {
        $now = now();
        $today = $now->copy()->startOfDay();

        // 1. Fetch Vehicles with currently active drivers
        // We strictly use start_date and end_date as defined in your assignments table
        $vehicles = Vehicle::with(['drivers' => function($query) use ($now) {
            $query->wherePivot('start_date', '<=', $now)
                ->where(function($q) use ($now) {
                    $q->where('driver_vehicle_assignments.end_date', '>=', $now)
                        ->orWhereNull('driver_vehicle_assignments.end_date');
                });
        }])->get();

        // 2. Fetch Today's Raw Events from the Hot Table (telemetry_recent)
        // We join vehicles and drivers here to get names for the Top 5 lists
        $rawEvents = DB::table('telemetry_recent')
            ->join('vehicles', 'vehicles.id', '=', 'telemetry_recent.vehicle_id')
            ->leftJoin('driver_vehicle_assignments', function($join) use ($now) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->where('driver_vehicle_assignments.start_date', '<=', $now)
                    ->where(function($q) use ($now) {
                        $q->where('driver_vehicle_assignments.end_date', '>=', $now)
                            ->orWhereNull('driver_vehicle_assignments.end_date');
                    });
            })
            ->leftJoin('drivers', 'drivers.id', '=', 'driver_vehicle_assignments.driver_id')
            ->leftJoin('users', 'users.id', '=', 'drivers.user_id')
            ->whereDate('telemetry_recent.created_at', $today)
            ->select([
                'vehicles.plate_number as plate',
                'users.name as driver_name',
                'telemetry_recent.event_type',
                'telemetry_recent.event_value as val'
            ])
            ->get();

        // 3. Aggregate Fleet-Wide Daily Totals
        $totalRefilled = $rawEvents->where('event_type', 'refill')->sum('val');
        $totalStolen = $rawEvents->where('event_type', 'theft')->sum('val');

        // 4. Construct the Dashboard Stats Object
        $stats = [
            'totalRefilled' => round($totalRefilled),
            'totalStolen'   => round($totalStolen),

            // Critical Fuel: Using live cached data in 'vehicles' table
            'criticalFuel' => $vehicles->filter(fn($v) => $v->tank_capacity > 0 && ($v->current_fuel / $v->tank_capacity) * 100 < 15)
                ->map(fn($v) => [
                    'plate' => $v->plate_number,
                    'val'   => round(($v->current_fuel / $v->tank_capacity) * 100)
                ])->values(),

            // Grouped Refills: Summing multiple entries for same vehicle
            'fillings' => $rawEvents->where('event_type', 'refill')
                ->groupBy('plate')
                ->map(fn($group) => [
                    'plate'       => $group->first()->plate,
                    'driver_name' => $group->first()->driver_name ?? 'Unassigned',
                    'val'         => round($group->sum('val'))
                ])
                ->sortByDesc('val')->take(5)->values(),

            // Grouped Thefts: Summing losses for same vehicle
            'thefts' => $rawEvents->where('event_type', 'theft')
                ->groupBy('plate')
                ->map(fn($group) => [
                    'plate'       => $group->first()->plate,
                    'driver_name' => $group->first()->driver_name ?? 'Unassigned',
                    'val'         => round($group->sum('val'))
                ])
                ->sortByDesc('val')->take(5)->values(),

            // Stationary Units: Units not moved in 24h
            'longStops' => $vehicles->whereNotNull('stationary_at')
                ->where('stationary_at', '<', $now->copy()->subHours(24))
                ->map(fn($v) => [
                    'plate' => $v->plate_number,
                    'driver' => $v->drivers->first()->name ?? 'N/A'
                ])->values(),

            // Placeholders for external logic modules
            'breakdowns'  => [],
            'grounded'    => [],
            'fuelRequests' => []
        ];

        return response()->json([
            'totalUnits' => $vehicles->count(),
            'stats'      => $stats
        ]);
    }

    public function getDetailedReport(Request $request, $type)
    {
        $start = $request->query('start', Carbon::today()->toDateTimeString());
        $end = $request->query('end', now()->toDateTimeString());

        // Strategy: Hot vs Cold Table Lookup
        $useHistory = Carbon::parse($start)->lt(now()->subHours(48));
        $table = $useHistory ? 'telemetry_history' : 'telemetry_recent';

        return DB::table($table)
            ->join('vehicles', 'vehicles.id', '=', "$table.vehicle_id")
            // Join Driver Pivot to see who was driving during the event
            ->leftJoin('driver_vehicle_assignments', function($join) use ($table) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->on('driver_vehicle_assignments.start_date', '<=', "$table.created_at")
                    ->where(function($q) use ($table) {
                        $q->on('driver_vehicle_assignments.end_date', '>=', "$table.created_at")
                            ->orWhereNull('driver_vehicle_assignments.end_date');
                    });
            })
            ->leftJoin('drivers', 'drivers.id', '=', 'driver_vehicle_assignments.driver_id')
            ->leftJoin('users', 'users.id', '=', 'drivers.user_id')
            ->whereBetween("$table.created_at", [$start, $end])
            ->when($type === 'thefts', fn($q) => $q->where('event_type', 'theft'))
            ->when($type === 'fillings', fn($q) => $q->where('event_type', 'refill'))
            ->select([
                'vehicles.plate_number',
                'users.name as driver_name',
                "$table.event_value as val",
                "$table.created_at as time",
                $useHistory ? "$table.lat" : DB::raw("ST_Y(location) as lat"),
                $useHistory ? "$table.lon" : DB::raw("ST_X(location) as lon")
            ])
            ->orderBy('time', 'desc')
            ->get();
    }
}
