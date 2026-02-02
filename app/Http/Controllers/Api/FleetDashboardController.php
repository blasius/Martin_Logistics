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
        $today = now()->startOfDay();

        // 1. Fetch Vehicles and attach Current Driver (matching your index logic)
        $vehicles = Vehicle::all()->map(function ($vehicle) {
            $currentDriver = DB::table('users')
                ->join('drivers', 'users.id', '=', 'drivers.user_id') // Ensure we link User -> Driver
                ->join('driver_vehicle_assignments', 'drivers.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                ->whereNull('driver_vehicle_assignments.end_date') // This is the Dispatch logic
                ->select('users.name')
                ->first();

            $vehicle->current_driver_name = $currentDriver ? $currentDriver->name : 'Unassigned';
            return $vehicle;
        });

        // 2. Fetch Today's Events with Current Driver attribution
        // We join the events to the same "Active Assignment" logic
        $rawEvents = DB::table('telemetry_recent')
            ->join('vehicles', 'vehicles.id', '=', 'telemetry_recent.vehicle_id')
            ->leftJoin('driver_vehicle_assignments', function($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->whereNull('driver_vehicle_assignments.end_date'); // Match Dispatch Logic
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

        // 3. Totals
        $totalRefilled = $rawEvents->where('event_type', 'refill')->sum('val');
        $totalStolen = $rawEvents->where('event_type', 'theft')->sum('val');

        // 4. Transform Stats
        $stats = [
            'totalRefilled' => round($totalRefilled),
            'totalStolen'   => round($totalStolen),

            'criticalFuel' => $vehicles->filter(fn($v) => $v->tank_capacity > 0 && ($v->current_fuel / $v->tank_capacity) * 100 < 15)
                ->map(fn($v) => [
                    'plate' => $v->plate_number,
                    'driver_name' => $v->current_driver_name,
                    'val'   => round(($v->current_fuel / $v->tank_capacity) * 100)
                ])->values(),

            'fillings' => $rawEvents->where('event_type', 'refill')
                ->groupBy('plate')
                ->map(fn($group) => [
                    'plate'       => $group->first()->plate,
                    'driver_name' => $group->first()->driver_name ?? 'Unassigned',
                    'val'         => round($group->sum('val'))
                ])
                ->sortByDesc('val')->take(5)->values(),

            'thefts' => $rawEvents->where('event_type', 'theft')
                ->groupBy('plate')
                ->map(fn($group) => [
                    'plate'       => $group->first()->plate,
                    'driver_name' => $group->first()->driver_name ?? 'Unassigned',
                    'val'         => round($group->sum('val'))
                ])
                ->sortByDesc('val')->take(5)->values(),

            'longStops' => $vehicles->whereNotNull('stationary_at')
                ->where('stationary_at', '<', now()->subHours(24))
                ->map(fn($v) => [
                    'plate' => $v->plate_number,
                    'driver_name' => $v->current_driver_name
                ])->values(),

            'breakdowns' => [], 'grounded' => [], 'fuelRequests' => []
        ];

        return response()->json(['totalUnits' => $vehicles->count(), 'stats' => $stats]);
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
