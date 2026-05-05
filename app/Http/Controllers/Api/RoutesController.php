<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;
use Illuminate\Support\Facades\DB;
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
}
