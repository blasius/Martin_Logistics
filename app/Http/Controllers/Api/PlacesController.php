<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Place;
use Illuminate\Support\Facades\DB;

class PlacesController extends Controller
{
    public function index()
    {
        $places = Place::select('id', 'place_key', 'name', 'type', 'description', 'country', 'county', 'city', 'address', 'latitude', 'longitude', 'radius_meters')->get();
        return response()->json($places);
    }

    public function show($id)
    {
        $place = Place::select('id', 'place_key', 'name', 'type', 'description', 'country', 'county', 'city', 'address', 'latitude', 'longitude', 'radius_meters')->findOrFail($id);
        return response()->json($place);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'country' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|numeric|min:0',
        ]);

        $pointWkt = "POINT({$validated['longitude']} {$validated['latitude']})";

        $place = Place::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'country' => $validated['country'] ?? null,
            'county' => $validated['county'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'location' => DB::raw("ST_GeomFromText('{$pointWkt}', 4326)"),
            'radius_meters' => $validated['radius_meters'] ?? 50,
        ]);

        $freshPlace = Place::select('id', 'place_key', 'name', 'type', 'description', 'country', 'county', 'city', 'address', 'latitude', 'longitude', 'radius_meters')->find($place->id);

        return response()->json(['message' => 'Place created successfully', 'place' => $freshPlace]);
    }

    public function update(Request $request, $id)
    {
        $place = Place::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'country' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|numeric|min:0',
        ]);

        $pointWkt = "POINT({$validated['longitude']} {$validated['latitude']})";

        $place->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'country' => $validated['country'] ?? null,
            'county' => $validated['county'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'location' => DB::raw("ST_GeomFromText('{$pointWkt}', 4326)"),
            'radius_meters' => $validated['radius_meters'] ?? 50,
        ]);

        $freshPlace = Place::select('id', 'place_key', 'name', 'type', 'description', 'country', 'county', 'city', 'address', 'latitude', 'longitude', 'radius_meters')->find($place->id);

        return response()->json(['message' => 'Place updated successfully', 'place' => $freshPlace]);
    }

    public function destroy($id)
    {
        $place = Place::findOrFail($id);
        $place->delete();
        return response()->json(['message' => 'Place deleted successfully']);
    }
}
