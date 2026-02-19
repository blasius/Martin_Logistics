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

        // 1. VEHICLES: Search by plate_number and attach current Driver (User)
        $vehicles = Vehicle::where('plate_number', 'LIKE', "%{$q}%")
            ->limit(5)
            ->get()
            ->map(function ($vehicle) {
                $currentDriver = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->select('users.name')
                    ->first();

                return [
                    'id' => $vehicle->id,
                    'label' => $vehicle->plate_number,
                    'sublabel' => $currentDriver ? "Driver: {$currentDriver->name}" : 'No Driver Assigned',
                    'type' => 'Vehicle'
                ];
            });

        // 2. AVAILABLE DRIVERS: Search via 'users' table, excluding active assignments
        $assignedUserIds = DB::table('driver_vehicle_assignments')
            ->whereNull('end_date')
            ->pluck('driver_id');

        $drivers = Driver::with('user')
            ->whereHas('user', function($query) use ($q) {
                $query->where('name', 'LIKE', "%{$q}%");
            })
            ->whereNotIn('user_id', $assignedUserIds)
            ->limit(5)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'label' => $d->user->name,
                'sublabel' => $d->phone ?? 'Available Driver',
                'type' => 'Driver'
            ]);

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
}
