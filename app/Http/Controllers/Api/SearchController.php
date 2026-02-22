<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Trailer;
use App\Models\Trip;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->query('q');
        if (!$q) return response()->json([]);

        // 1. DRIVERS (Search by User Name + Status Color)
        $drivers = Driver::with('user')
            ->whereHas('user', function($query) use ($q) {
                $query->where('name', 'LIKE', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($driver) {
                $activeAssignment = DB::table('driver_vehicle_assignments')
                    ->join('vehicles', 'vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->where('driver_id', $driver->user_id)
                    ->whereNull('end_date')
                    ->select('vehicles.plate_number')
                    ->first();

                return [
                    'id' => $driver->id,
                    'label' => $driver->user->name,
                    'sublabel' => $activeAssignment
                        ? "Driving: {$activeAssignment->plate_number}"
                        : "Available",
                    'status_color' => $activeAssignment ? 'orange' : 'green',
                    'type' => 'Driver'
                ];
            });

        // 2. VEHICLES (Plate Search + Status Color)
        $vehicles = Vehicle::where('plate_number', 'LIKE', "%{$q}%")
            ->limit(5)
            ->get()
            ->map(function ($vehicle) {
                $hasDriver = DB::table('driver_vehicle_assignments')
                    ->where('vehicle_id', $vehicle->id)
                    ->whereNull('end_date')
                    ->exists();

                return [
                    'id' => $vehicle->id,
                    'label' => $vehicle->plate_number,
                    'sublabel' => $hasDriver ? "Assigned" : "Unassigned (Idle)",
                    'status_color' => $hasDriver ? 'indigo' : 'slate',
                    'type' => 'Vehicle'
                ];
            });

        // 3. AVAILABLE TRAILERS: Search by plate_number, excluding active assignments
        $assignedTrailerIds = DB::table('trailer_assignments')
            ->whereNull('unassigned_at')
            ->pluck('trailer_id');

        $trailers = Trailer::where('status', 'active')
            ->where('plate_number', 'LIKE', "%{$q}%") // Based on your migration
            ->whereNotIn('id', $assignedTrailerIds)
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'label' => $t->plate_number,
                'sublabel' => "Type: " . ($t->type ?? 'Standard'),
                'type' => 'Trailer'
            ]);

        // 4. ORDERS & CLIENTS: Search by Reference or Client Name
        $orders = Order::with('client')
            ->where('reference', 'LIKE', "%{$q}%")
            ->orWhereHas('client', function($query) use ($q) {
                $query->where('name', 'LIKE', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'label' => "Order: {$o->reference}",
                'sublabel' => "{$o->client->name} | {$o->status}",
                'type' => 'Order'
            ]);

        // 5. TRIPS: Search by Snapshot data or Status
        $trips = Trip::where('vehicle_plate_snapshot', 'LIKE', "%{$q}%")
            ->orWhere('driver_name_snapshot', 'LIKE', "%{$q}%")
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'label' => "Trip: {$t->vehicle_plate_snapshot}",
                'sublabel' => "Status: {$t->status} | {$t->driver_name_snapshot}",
                'type' => 'Trip'
            ]);

        return response()->json([
            ['type' => 'Vehicles', 'items' => $vehicles],
            ['type' => 'Drivers', 'items' => $drivers],
            ['type' => 'Trailers', 'items' => $trailers],
            ['type' => 'Orders', 'items' => $orders],
            ['type' => 'Trips', 'items' => $trips],
        ]);
    }

    // app/Http/Controllers/Api/DriverController.php
    public function showDriver(Driver $driver)
    {
        // Load the user identity and trip count
        $driver->load(['user', 'trips.vehicle']);

        // Calculate complex stats
        $stats = [
            'total_trips' => $driver->trips()->count(),
            'active_since' => $driver->created_at->diffForHumans(),
            'assigned_vehicle' => DB::table('driver_vehicle_assignments')
                ->join('vehicles', 'vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                ->where('driver_id', $driver->user_id)
                ->whereNull('end_date')
                ->value('plate_number'),
            'safety_score' => 95, // Example: logic for calculating score based on fines/speeding
            'trip_history' => $driver->trips()->latest()->limit(5)->get()
        ];

        return response()->json([
            'driver' => $driver,
            'stats' => $stats
        ]);
    }

    public function showVehicle(Vehicle $vehicle)
    {
        // Emulate your index logic for current driver
        $currentDriver = DB::table('users')
            ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
            ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
            ->whereNull('driver_vehicle_assignments.end_date')
            ->value('name');

        return response()->json([
            'vehicle' => $vehicle,
            'stats' => [
                'current_driver' => $currentDriver,
                'total_trips' => DB::table('trips')->where('vehicle_id', $vehicle->id)->count(),
            ]
        ]);
    }
}
