<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RoutesController extends Controller
{
    public function index()
    {
        // Explicitly selecting columns to avoid fetching the binary 'path_geometry' column
        // which causes Malformed UTF-8 characters error when converting to JSON.
        $routes = Route::select('id', 'name', 'fleet_key', 'allowed_deviation_meters', 'estimated_distance_km')->get();

        return response()->json($routes);
    }

    public function show($id)
    {
        // Select explicitly to avoid the binary geometry column
        $route = Route::select('id', 'name', 'fleet_key', 'allowed_deviation_meters', 'estimated_distance_km', 'path')->findOrFail($id);

        return response()->json($route);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_deviation_meters' => 'required|numeric',
            'path' => 'required|array|min:2', // Must have at least a start and end
        ]);

        $wktPoints = collect($validated['path'])
            ->map(fn($p) => "{$p['lng']} {$p['lat']}")
            ->implode(', ');

        $lineStringWkt = "LINESTRING($wktPoints)";

        // Generate a unique fleet key
        $fleetKey = 'RT-' . strtoupper(Str::random(6));

        $route = Route::create([
            'name' => $validated['name'],
            'fleet_key' => $fleetKey,
            'allowed_deviation_meters' => $validated['allowed_deviation_meters'],
            'path' => $validated['path'],
            'path_geometry' => DB::raw("ST_GeomFromText('$lineStringWkt', 4326)"),
            'estimated_distance_km' => DB::raw("(ST_Length(ST_GeomFromText('$lineStringWkt', 4326), 'kilometre'))")
        ]);

        // Fetch fresh model excluding geometry
        $freshRoute = Route::select('id', 'name', 'fleet_key', 'allowed_deviation_meters', 'estimated_distance_km', 'path')->find($route->id);

        return response()->json(['message' => 'Route created successfully', 'route' => $freshRoute]);
    }

    public function update(Request $request, $id)
    {
        $route = Route::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_deviation_meters' => 'required|numeric',
            'path' => 'required|array|min:2',
        ]);

        $wktPoints = collect($validated['path'])
            ->map(fn($p) => "{$p['lng']} {$p['lat']}")
            ->implode(', ');

        $lineStringWkt = "LINESTRING($wktPoints)";

        $route->update([
            'name' => $validated['name'],
            'allowed_deviation_meters' => $validated['allowed_deviation_meters'],
            'path' => $validated['path'],
            'path_geometry' => DB::raw("ST_GeomFromText('$lineStringWkt', 4326)"),
            'estimated_distance_km' => DB::raw("(ST_Length(ST_GeomFromText('$lineStringWkt', 4326), 'kilometre'))")
        ]);

        // Fetch fresh model excluding geometry
        $freshRoute = Route::select('id', 'name', 'fleet_key', 'allowed_deviation_meters', 'estimated_distance_km', 'path')->find($route->id);

        return response()->json(['message' => 'Route updated successfully', 'route' => $freshRoute]);
    }

    public function destroy($id)
    {
        $route = Route::findOrFail($id);
        $route->delete();

        return response()->json(['message' => 'Route deleted successfully']);
    }

    public function routeFromOsrm(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|string',
            'to'   => 'required|string',
            'waypoints' => 'nullable|array',
            'waypoints.*' => 'string',
        ]);

        $coords = collect();

        // Parse optional intermediate waypoints first
        if (!empty($validated['waypoints'])) {
            foreach ($validated['waypoints'] as $wp) {
                $parts = array_map('floatval', explode(',', $wp));
                if (count($parts) === 2) {
                    $coords->push("{$parts[1]},{$parts[0]}");
                }
            }
        }

        // Parse start and end (OSRM expects start;waypoints;end)
        $fromParts = array_map('floatval', explode(',', $validated['from']));
        $toParts = array_map('floatval', explode(',', $validated['to']));

        if (count($fromParts) !== 2 || count($toParts) !== 2) {
            return response()->json(['message' => 'Invalid coordinates format. Use "lat,lng"'], 422);
        }

        $coords->prepend("{$fromParts[1]},{$fromParts[0]}");
        $coords->push("{$toParts[1]},{$toParts[0]}");

        $coordString = $coords->implode(';');
        $osrmUrl = config('services.osrm.url');
        $url = "{$osrmUrl}/route/v1/driving/{$coordString}?overview=full&geometries=geojson&alternatives=true";

        try {
            $response = Http::timeout(10)->get($url);
            $data = $response->json();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Routing service is unavailable.'], 502);
        }

        if (!isset($data['routes']) || empty($data['routes'])) {
            return response()->json(['message' => 'No route found between the given points.'], 422);
        }

        $routes = collect($data['routes'])->map(function ($route) {
            $coords = collect($route['geometry']['coordinates'])->map(fn ($c) => [
                'lat' => round($c[1], 6),
                'lng' => round($c[0], 6),
            ])->values();

            return [
                'path'        => $coords,
                'distance_km' => round($route['distance'] / 1000, 2),
                'duration_min'=> round($route['duration'] / 60, 1),
            ];
        });

        return response()->json([
            'routes' => $routes->values(),
        ]);
    }
}
