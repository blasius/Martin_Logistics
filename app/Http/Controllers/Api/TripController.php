<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\ClearanceBypassRequest;
use App\Services\ClearanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    public function __construct(protected ClearanceService $clearanceService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'       => 'required|exists:orders,id',
            'assignment'     => 'required|string',
            'route_id'       => 'nullable|exists:routes,id',
            'allocated_weight' => 'nullable|numeric',
            'status'         => 'required|in:pending,assigned,on_route',
        ]);

        // Parse assignment string
        $vehicleId = null;
        $driverId = null;
        if (str_contains($validated['assignment'], '-')) {
            [$type, $id] = explode('-', $validated['assignment']);
            if ($type === 'vehicle') $vehicleId = (int) $id;
            if ($type === 'driver') $driverId = (int) $id;
        }

        // Run clearance checks if vehicle is assigned
        if ($vehicleId) {
            $checks = $this->clearanceService->check($vehicleId, $driverId);
            $blocking = $this->clearanceService->blockingChecks($checks);

            if (!empty($blocking)) {
                // Check for approved bypasses for this vehicle+driver combo
                $approvedBypasses = ClearanceBypassRequest::whereIn('check_name', collect($blocking)->pluck('check'))
                    ->where('status', 'approved')
                    ->pluck('check_name')
                    ->toArray();

                $stillBlocked = array_values(array_filter($blocking, fn($c) => !in_array($c['check'], $approvedBypasses)));

                if (!empty($stillBlocked)) {
                    return response()->json([
                        'message' => 'Pre-trip clearance checks failed',
                        'blockers' => array_map(fn($c) => [
                            'check' => $c['check'],
                            'label' => $c['label'],
                            'message' => $c['message'],
                        ], $stillBlocked),
                    ], 422);
                }
            }
        }

        return DB::transaction(function () use ($validated, $vehicleId, $driverId) {
            $trip = Trip::create([
                'order_id'       => $validated['order_id'],
                'vehicle_id'     => $vehicleId,
                'driver_id'      => $driverId,
                'route_id'       => $validated['route_id'],
                'status'         => $validated['status'],
                'created_by'     => Auth::id() ?? 1,
            ]);

            $order = Order::find($validated['order_id']);
            if ($order) {
                $remaining = max(0, ($order->remaining_tonnage ?? $order->tonnage) - ($validated['allocated_weight'] ?? 0));
                $orderUpdate = ['remaining_tonnage' => $remaining];
                if ($remaining == 0) {
                    $orderUpdate['status'] = 'in_transit';
                }
                $order->update($orderUpdate);
            }

            return response()->json([
                'message' => 'Trip successfully dispatched.',
                'trip_id' => $trip->id,
            ], 201);
        });
    }

    public function searchAssignments(Request $request)
    {
        $q = $request->query('q');
        if (strlen($q) < 2) return response()->json([]);

        $vehicles = Vehicle::where('plate_number', 'like', "%{$q}%")
            ->limit(5)->get(['id', 'plate_number', 'capacity']);

        $results = [];
        foreach ($vehicles as $v) {
            $driverName = DB::table('users')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('driver_vehicle_assignments.vehicle_id', $v->id)
                ->whereNull('driver_vehicle_assignments.end_date')
                ->value('users.name');

            $trailerPlate = DB::table('trailers')
                ->join('trailer_assignments', 'trailers.id', '=', 'trailer_assignments.trailer_id')
                ->where('trailer_assignments.vehicle_id', $v->id)
                ->whereNull('trailer_assignments.unassigned_at')
                ->value('trailers.plate_number');

            $results[] = [
                'id' => "vehicle-{$v->id}",
                'label' => $v->plate_number,
                'driver_name' => $driverName,
                'trailer_plate' => $trailerPlate,
                'type' => 'vehicle',
                'capacity' => (float) $v->capacity,
                'ratio' => 35.0,
                'age' => 5,
            ];
        }

        $drivers = Driver::whereHas('user', fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->with('user:id,name')
            ->limit(5)->get();

        foreach ($drivers as $d) {
            $results[] = [
                'id' => "driver-{$d->id}",
                'label' => "Driver: {$d->user->name}",
                'driver_name' => $d->user->name,
                'trailer_plate' => null,
                'type' => 'driver',
                'capacity' => null,
                'ratio' => 0,
                'age' => 0,
            ];
        }

        return response()->json($results);
    }
}
