<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Place;
use App\Models\VehicleSnapshot;
use Illuminate\Support\Facades\DB;
use App\Exports\StationaryVehiclesExport;
use App\Exports\OfflineVehiclesExport;
use Maatwebsite\Excel\Facades\Excel;

class TrackerController extends Controller
{
    public function index(Request $request)
    {
        $hours = max(1, (int) $request->query('hours', 12));
        $since = now()->subHours($hours);

        $telemetryBounds = DB::table('telemetry_points')
            ->select(
                'vehicle_id',
                DB::raw('COUNT(*) as point_count'),
                DB::raw('MIN(latitude) as min_lat'),
                DB::raw('MAX(latitude) as max_lat'),
                DB::raw('MIN(longitude) as min_lng'),
                DB::raw('MAX(longitude) as max_lng')
            )
            ->where('recorded_at', '>=', $since)
            ->groupBy('vehicle_id')
            ->get()
            ->keyBy('vehicle_id');

        $vehicles = Vehicle::query()
            ->select('vehicles.id', 'vehicles.plate_number')
            ->with('snapshot')
            ->get()
            ->map(function ($vehicle) use ($telemetryBounds) {
                $currentDriver = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->join('drivers', 'users.id', '=', 'drivers.user_id')
                    ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->select('users.id', 'users.name')
                    ->first();

                $distanceKm = null;
                $pointCount = 0;
                if ($telemetryBounds->has($vehicle->id)) {
                    $b = $telemetryBounds->get($vehicle->id);
                    $pointCount = (int) $b->point_count;
                    if ($pointCount > 1) {
                        $distanceKm = round(
                            $this->haversineDistance($b->min_lat, $b->min_lng, $b->max_lat, $b->max_lng) / 1000,
                            2
                        );
                    } else {
                        $distanceKm = 0;
                    }
                }

                return [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'driver_name' => $currentDriver?->name,
                    'snapshot' => $vehicle->snapshot ? [
                        'latitude' => (float) $vehicle->snapshot->latitude,
                        'longitude' => (float) $vehicle->snapshot->longitude,
                        'speed' => (float) $vehicle->snapshot->speed,
                        'last_seen_at' => $vehicle->snapshot->last_seen_at,
                        'is_moving' => $vehicle->snapshot->is_moving,
                        'ignition' => $vehicle->snapshot->ignition,
                        'fuel_level' => (float) $vehicle->snapshot->fuel_level,
                    ] : null,
                    'distance_km' => $distanceKm,
                    'telemetry_point_count' => $pointCount,
                ];
            });

        return response()->json($vehicles);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        if (!$q || strlen(trim($q)) < 2) {
            return response()->json([]);
        }

        $q = trim($q);

        $vehicles = Vehicle::query()
            ->select('vehicles.id', 'vehicles.plate_number')
            ->where(function ($query) use ($q) {
                $query->where('vehicles.plate_number', 'LIKE', "%{$q}%")
                    ->orWhereExists(function ($sub) use ($q) {
                        $sub->select(DB::raw(1))
                            ->from('driver_vehicle_assignments')
                            ->join('users', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                            ->whereColumn('driver_vehicle_assignments.vehicle_id', 'vehicles.id')
                            ->whereNull('driver_vehicle_assignments.end_date')
                            ->where('users.name', 'LIKE', "%{$q}%");
                    })
                    ->orWhereHas('trailers', function ($q2) use ($q) {
                        $q2->where('trailers.plate_number', 'LIKE', "%{$q}%");
                    });
            })
            ->with(['snapshot', 'currentAssignment.trailer'])
            ->limit(10)
            ->get()
            ->map(function ($vehicle) {
                $currentDriver = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->join('drivers', 'users.id', '=', 'drivers.user_id')
                    ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->select('users.id', 'users.name')
                    ->first();

                return [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'driver_name' => $currentDriver?->name,
                    'trailer_plate' => optional($vehicle->currentAssignment?->trailer)->plate_number,
                    'snapshot' => $vehicle->snapshot ? [
                        'latitude' => (float) $vehicle->snapshot->latitude,
                        'longitude' => (float) $vehicle->snapshot->longitude,
                        'speed' => (float) $vehicle->snapshot->speed,
                        'last_seen_at' => $vehicle->snapshot->last_seen_at,
                        'is_moving' => $vehicle->snapshot->is_moving,
                        'ignition' => $vehicle->snapshot->ignition,
                    ] : null,
                ];
            });

        return response()->json($vehicles);
    }

    public function show(Request $request, $vehicleId)
    {
        $vehicle = Vehicle::with('currentAssignment.trailer')->findOrFail($vehicleId);

        $currentDriver = DB::table('users')
            ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
            ->join('drivers', 'users.id', '=', 'drivers.user_id')
            ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
            ->whereNull('driver_vehicle_assignments.end_date')
            ->select('users.id', 'users.name')
            ->first();

        $snapshot = VehicleSnapshot::where('vehicle_id', $vehicleId)->first();

        $nearestPlace = null;
        if ($snapshot && $snapshot->latitude && $snapshot->longitude) {
            $pointWkt = "POINT({$snapshot->longitude} {$snapshot->latitude})";
            $nearestPlace = Place::select(
                'id', 'place_key', 'name', 'type', 'city', 'latitude', 'longitude', 'radius_meters',
                DB::raw("ROUND(ST_Distance_Sphere(location, ST_GeomFromText('{$pointWkt}', 4326)), 0) AS distance_meters")
            )
                ->orderBy('distance_meters')
                ->limit(1)
                ->first();
        }

        $dateFrom = $request->query('date_from', now()->startOfDay()->toDateTimeString());
        $dateTo = $request->query('date_to', now()->endOfDay()->toDateTimeString());

        $breadcrumb = DB::table('telemetry_points')
            ->select('id', 'latitude', 'longitude', 'recorded_at', 'speed', 'ignition', 'heading')
            ->where('vehicle_id', $vehicleId)
            ->where('recorded_at', '>=', $dateFrom)
            ->where('recorded_at', '<=', $dateTo)
            ->orderBy('recorded_at')
            ->limit(1000)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'recorded_at' => $p->recorded_at,
                'speed' => (float) $p->speed,
                'ignition' => (bool) $p->ignition,
                'heading' => (float) $p->heading,
            ]);

        $allPlaces = Place::select('id', 'place_key', 'name', 'type', 'latitude', 'longitude', 'radius_meters')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'place_key' => $p->place_key,
                'name' => $p->name,
                'type' => $p->type,
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'radius_meters' => (int) $p->radius_meters,
            ]);

        $visitedPlaceIds = [];
        if ($breadcrumb->isNotEmpty() && $allPlaces->isNotEmpty()) {
            $sampled = $breadcrumb->count() > 200
                ? $breadcrumb->filter(fn($p, $i) => $i % max(1, intdiv($breadcrumb->count(), 200)) === 0)
                : $breadcrumb;

            foreach ($sampled as $point) {
                foreach ($allPlaces as $place) {
                    $d = $this->haversineDistance(
                        $point['latitude'], $point['longitude'],
                        $place['latitude'], $place['longitude']
                    );
                    if ($d <= $place['radius_meters']) {
                        $visitedPlaceIds[$place['id']] = [
                            'place_id' => $place['id'],
                            'name' => $place['name'],
                            'type' => $place['type'],
                            'recorded_at' => $point['recorded_at'],
                            'distance' => round($d, 0),
                        ];
                    }
                }
            }
        }

        return response()->json([
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'driver_name' => $currentDriver?->name,
                'trailer_plate' => optional($vehicle->currentAssignment?->trailer)->plate_number,
            ],
            'snapshot' => $snapshot ? [
                'latitude' => (float) $snapshot->latitude,
                'longitude' => (float) $snapshot->longitude,
                'speed' => (float) $snapshot->speed,
                'fuel_level' => (float) $snapshot->fuel_level,
                'last_seen_at' => $snapshot->last_seen_at,
                'is_moving' => (bool) $snapshot->is_moving,
                'ignition' => (bool) $snapshot->ignition,
            ] : null,
            'nearest_place' => $nearestPlace,
            'breadcrumb' => $breadcrumb,
            'places' => $allPlaces,
            'visited_places' => array_values($visitedPlaceIds),
        ]);
    }

    public function exportStationary(Request $request)
    {
        $hours = max(1, (int) $request->query('hours', 12));
        $since = now()->subHours($hours);

        $telemetryBounds = DB::table('telemetry_points')
            ->select(
                'vehicle_id',
                DB::raw('COUNT(*) as point_count'),
                DB::raw('MIN(latitude) as min_lat'),
                DB::raw('MAX(latitude) as max_lat'),
                DB::raw('MIN(longitude) as min_lng'),
                DB::raw('MAX(longitude) as max_lng')
            )
            ->where('recorded_at', '>=', $since)
            ->groupBy('vehicle_id')
            ->get()
            ->keyBy('vehicle_id');

        $vehicles = Vehicle::query()
            ->select('vehicles.id', 'vehicles.plate_number')
            ->with('snapshot')
            ->get()
            ->filter(function ($vehicle) use ($telemetryBounds, $hours) {
                $snap = $vehicle->snapshot;
                if (!$snap || !$snap->last_seen_at) return false;
                $age = now()->diffInSeconds($snap->last_seen_at) * 1000;
                $thresholdMs = $hours * 60 * 60 * 1000;
                if ($age > $thresholdMs) return false;

                $b = $telemetryBounds->get($vehicle->id);
                if ($b && $b->point_count > 1) {
                    $d = $this->haversineDistance($b->min_lat, $b->min_lng, $b->max_lat, $b->max_lng) / 1000;
                    return $d < 2;
                }
                return !$snap->is_moving;
            })
            ->map(function ($vehicle) use ($telemetryBounds) {
                $currentDriver = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->join('drivers', 'users.id', '=', 'drivers.user_id')
                    ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->select('users.id', 'users.name')
                    ->first();

                $distanceKm = null;
                $b = $telemetryBounds->get($vehicle->id);
                if ($b && $b->point_count > 1) {
                    $distanceKm = round(
                        $this->haversineDistance($b->min_lat, $b->min_lng, $b->max_lat, $b->max_lng) / 1000,
                        2
                    );
                }

                return [
                    'plate_number' => $vehicle->plate_number,
                    'driver_name' => $currentDriver?->name,
                    'snapshot' => $vehicle->snapshot ? [
                        'last_seen_at' => $vehicle->snapshot->last_seen_at,
                    ] : null,
                    'distance_km' => $distanceKm,
                ];
            })->values();

        return Excel::download(
            new StationaryVehiclesExport($vehicles),
            'stationary_vehicles_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    public function exportOffline(Request $request)
    {
        $hours = max(1, (int) $request->query('hours', 12));
        $since = now()->subHours($hours);

        $vehicles = Vehicle::query()
            ->select('vehicles.id', 'vehicles.plate_number')
            ->with('snapshot')
            ->get()
            ->filter(function ($vehicle) use ($hours) {
                $snap = $vehicle->snapshot;
                if (!$snap || !$snap->last_seen_at) return true;
                $age = now()->diffInSeconds($snap->last_seen_at) * 1000;
                return $age > $hours * 60 * 60 * 1000;
            })
            ->map(function ($vehicle) {
                $currentDriver = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->join('drivers', 'users.id', '=', 'drivers.user_id')
                    ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->select('users.id', 'users.name')
                    ->first();

                return [
                    'plate_number' => $vehicle->plate_number,
                    'driver_name' => $currentDriver?->name,
                    'snapshot' => $vehicle->snapshot ? [
                        'last_seen_at' => $vehicle->snapshot->last_seen_at,
                    ] : null,
                ];
            })->values();

        return Excel::download(
            new OfflineVehiclesExport($vehicles),
            'offline_vehicles_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
