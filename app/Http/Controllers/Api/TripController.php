<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    /**
     * Store a newly created trip and link resources.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'       => 'required|exists:orders,id',
            'assignment'     => 'required|string', // e.g., "vehicle-5" or "driver-12"
            'route_id'       => 'nullable|exists:routes,id',
            'departure_time' => 'nullable|date',
            'status'         => 'required|in:pending,assigned,on_route',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Parse the Assignment String (Filament Logic)
            $vehicleId = null;
            $driverId = null;

            if (str_contains($validated['assignment'], '-')) {
                [$type, $id] = explode('-', $validated['assignment']);
                if ($type === 'vehicle') $vehicleId = $id;
                if ($type === 'driver') $driverId = $id;
            }

            // 2. Create the Trip Record
            $trip = Trip::create([
                'order_id'       => $validated['order_id'],
                'vehicle_id'     => $vehicleId,
                'driver_id'      => $driverId,
                'route_id'       => $validated['route_id'],
                'status'         => $validated['status'],
                'departure_time' => $validated['departure_time'],
                'created_by'     => Auth::id(),
            ]);

            // 3. Update the Order Status
            // This prevents the order from appearing in the "Pending" list again.
            Order::where('id', $validated['order_id'])->update([
                'status' => 'dispatched'
            ]);

            return response()->json([
                'message' => 'Trip successfully dispatched.',
                'trip_id' => $trip->id
            ], 201);
        });
    }

    /**
     * Unified search for Vehicles and Drivers.
     */
    public function searchAssignments(Request $request)
    {
        $q = $request->query('q');
        if (strlen($q) < 2) return response()->json([]);

        $vehicles = Vehicle::where('plate_number', 'like', "%{$q}%")
            ->limit(5)->get(['id', 'plate_number']);

        $drivers = Driver::whereHas('user', fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->with('user:id,name')
            ->limit(5)->get();

        $results = [];
        foreach ($vehicles as $v) {
            $results[] = ['id' => "vehicle-{$v->id}", 'label' => "Vehicle: {$v->plate_number}", 'type' => 'vehicle'];
        }
        foreach ($drivers as $d) {
            $results[] = ['id' => "driver-{$d->id}", 'label' => "Driver: {$d->user->name}", 'type' => 'driver'];
        }

        return response()->json($results);
    }
}
