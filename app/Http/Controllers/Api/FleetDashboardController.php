<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FleetDashboardController extends Controller
{
    // app/Http/Controllers/Api/FleetDashboardController.php

    public function getSnapshot()
    {
        $now = now();
        $today = Carbon::today();

        // 1. Fetch Vehicles with currently active drivers
        $vehicles = Vehicle::with(['drivers' => function($query) use ($now) {
            $query->wherePivot('start_date', '<=', $now)
                ->where(function($q) use ($now) {
                    $q->where('driver_vehicle_assignments.end_date', '>=', $now)
                        ->orWhereNull('driver_vehicle_assignments.end_date');
                });
        }])->get();

        // 2. Today's Events from telemetry_recent (Fast lookup)
        $todayEvents = DB::table('telemetry_recent')
            ->whereDate('created_at', $today)
            ->whereIn('event_type', ['theft', 'refill'])
            ->get(['vehicle_id', 'event_type', 'event_value']);

        return response()->json([
            'totalUnits' => $vehicles->count(),
            'stats' => [
                'criticalFuel' => $vehicles->filter(fn($v) => $v->tank_capacity > 0 && ($v->current_fuel / $v->tank_capacity) * 100 < 15)
                    ->map(fn($v) => ['id' => $v->id, 'plate' => $v->plate_number, 'val' => round(($v->current_fuel / $v->tank_capacity) * 100)])
                    ->values(),

                'thefts' => $todayEvents->where('event_type', 'theft')->values(),

                'fillings' => $todayEvents->where('event_type', 'refill')->values(),

                'longStops' => $vehicles->whereNotNull('stationary_at')
                    ->where('stationary_at', '<', now()->subHours(24))
                    ->map(fn($v) => ['plate' => $v->plate_number])->values(),

                'breakdowns' => [],
                'grounded' => [],
                'fuelRequests' => []
            ]
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
