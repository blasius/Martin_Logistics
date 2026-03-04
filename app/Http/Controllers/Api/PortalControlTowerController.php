<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalControlTowerController extends Controller
{
    public function index(Request $request)
    {
        $todayStart = Carbon::today();
        $todayEnd   = Carbon::today()->endOfDay();

        return response()->json([
            'summary'            => $this->summary($todayStart, $todayEnd),
            'live_status'        => $this->liveStatus(),
            'low_fuel_vehicles'  => $this->lowFuelVehicles(),
            'active_events'      => $this->activeEvents($todayStart, $todayEnd),
            'top_daily_distance' => $this->topDailyDistance(),
        ]);
    }

    /**
     * ==============================
     * FLEET SUMMARY (Today)
     * ==============================
     */
    private function summary($start, $end): array
    {
        $totalVehicles = DB::table('vehicles')
            ->where('status', 'active')
            ->count();

        $movingNow = DB::table('vehicle_snapshots')
            ->where('is_moving', true)
            ->count();

        $offline = DB::table('vehicle_snapshots')
            ->where('last_seen_at', '<', now()->subMinutes(10))
            ->count();

        $lowFuel = DB::table('vehicle_snapshots')
            ->where('low_fuel', true)
            ->count();

        $overspeedEventsToday = DB::table('telemetry_events')
            ->where('type', 'overspeed')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $fuelDrainToday = DB::table('telemetry_events')
            ->where('type', 'fuel_drain')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        return [
            'total_vehicles'       => $totalVehicles,
            'moving_now'           => $movingNow,
            'offline'              => $offline,
            'low_fuel_now'         => $lowFuel,
            'overspeed_today'      => $overspeedEventsToday,
            'fuel_drain_today'     => $fuelDrainToday,
        ];
    }

    /**
     * ==============================
     * LIVE VEHICLE STATUS
     * ==============================
     */
    private function liveStatus()
    {
        return DB::table('vehicle_snapshots as vs')
            ->join('vehicles as v', 'v.id', '=', 'vs.vehicle_id')
            ->leftJoin('driver_vehicle_assignments as dva', function ($join) {
                $join->on('v.id', '=', 'dva.vehicle_id')
                    ->whereNull('dva.end_date');
            })
            ->leftJoin('users as u', 'u.id', '=', 'dva.driver_id')
            ->orderByDesc('vs.last_seen_at')
            ->limit(100)
            ->get([
                'v.id',
                'v.plate_number',
                'u.name as driver_name',
                'vs.last_seen_at',
                'vs.latitude',
                'vs.longitude',
                'vs.speed',
                'vs.fuel_level',
                'vs.ignition',
                'vs.is_moving',
                'vs.is_overspeeding',
                'vs.low_fuel',
            ]);
    }

    /**
     * ==============================
     * LOW FUEL VEHICLES (Live)
     * ==============================
     */
    private function lowFuelVehicles()
    {
        return DB::table('vehicle_snapshots as vs')
            ->join('vehicles as v', 'v.id', '=', 'vs.vehicle_id')
            ->leftJoin('driver_vehicle_assignments as dva', function ($join) {
                $join->on('v.id', '=', 'dva.vehicle_id')
                    ->whereNull('dva.end_date');
            })
            ->leftJoin('users as u', 'u.id', '=', 'dva.driver_id')
            ->where('vs.low_fuel', true)
            ->orderBy('vs.fuel_level')
            ->limit(50)
            ->get([
                'v.plate_number',
                'u.name as driver_name',
                'vs.fuel_level as fuel_percent',
                'vs.last_seen_at',
            ]);
    }

    /**
     * ==============================
     * TODAY'S EVENTS (Recent)
     * ==============================
     */
    private function activeEvents($start, $end)
    {
        return DB::table('telemetry_events as e')
            ->join('vehicles as v', 'v.id', '=', 'e.vehicle_id')
            ->whereBetween('e.occurred_at', [$start, $end])
            ->orderByDesc('e.occurred_at')
            ->limit(50)
            ->get([
                'v.plate_number',
                'e.type',
                'e.value',
                'e.occurred_at',
            ]);
    }

    /**
     * ==============================
     * TOP DAILY DISTANCE
     * ==============================
     */
    private function topDailyDistance()
    {
        return DB::table('vehicle_daily_stats as s')
            ->join('vehicles as v', 'v.id', '=', 's.vehicle_id')
            ->where('s.date', Carbon::today())
            ->orderByDesc('s.distance_km')
            ->limit(10)
            ->get([
                'v.plate_number',
                's.distance_km',
                's.fuel_consumed',
                's.engine_hours',
            ]);
    }
}
