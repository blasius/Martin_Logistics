<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;
use Illuminate\Support\Facades\DB;

class RoutesController extends Controller
{
    //
    public function index ()
    {

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fleet_key' => 'required|string|max:50',
            'allowed_deviation_meters' => 'required|numeric',
            'path' => 'required|array|min:2', // Must have at least a start and end
        ]);

        // 1. Convert Vue array to WKT (Well-Known Text) LineString format
        // MySQL expects: "LINESTRING(lng lat, lng lat, ...)"
        $wktPoints = collect($validated['path'])
            ->map(fn($p) => "{$p['lng']} {$p['lat']}")
            ->implode(', ');

        $lineStringWkt = "LINESTRING($wktPoints)";

        // 2. Insert into the database
        // We use ST_GeomFromText to transform the string into a binary spatial object
        Route::create([
            'name' => $validated['name'],
            'fleet_key' => $validated['fleet_key'],
            'allowed_deviation_meters' => $validated['allowed_deviation_meters'],
            'path' => json_encode($validated['path']), // Keep original JSON for easy frontend re-editing
            'path_geometry' => DB::raw("ST_GeomFromText('$lineStringWkt', 4326)"),
            'estimated_distance_km' => DB::raw("(ST_Length(ST_GeomFromText('$lineStringWkt', 4326), 'kilometre'))")
        ]);

        return response()->json(['message' => 'Route created successfully']);
    }
}
