<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Place;
use App\Models\VehicleSnapshot;
use Illuminate\Support\Facades\DB;

class TrackerController extends Controller
{
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
