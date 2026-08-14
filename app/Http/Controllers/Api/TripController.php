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

    public function index(Request $request)
    {
        $query = Trip::with(['order.client', 'route']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('reference', 'like', "%{$q}%")
                    ->orWhere('vehicle_plate_snapshot', 'like', "%{$q}%")
                    ->orWhere('driver_name_snapshot', 'like', "%{$q}%")
                    ->orWhereHas('order', function ($order) use ($q) {
                        $order->where('reference', 'like', "%{$q}%")
                            ->orWhere('origin', 'like', "%{$q}%")
                            ->orWhere('destination', 'like', "%{$q}%")
                            ->orWhereHas('client.user', fn ($user) => $user->where('name', 'like', "%{$q}%"));
                    });
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $query->orderByDesc('created_at');

        $trips = $query->paginate($request->integer('per_page', 15));

        $trips->getCollection()->transform(fn (Trip $trip) => $this->tripListItem($trip));

        $statusCounts = Trip::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'data' => $trips->items(),
            'meta' => [
                'current_page' => $trips->currentPage(),
                'last_page' => $trips->lastPage(),
                'total' => $trips->total(),
                'per_page' => $trips->perPage(),
            ],
            'counts' => [
                'pending' => $statusCounts['pending'] ?? 0,
                'assigned' => $statusCounts['assigned'] ?? 0,
                'on_route' => $statusCounts['on_route'] ?? 0,
                'delivered' => $statusCounts['delivered'] ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
                'total' => $statusCounts->sum(),
            ],
        ]);
    }

    public function show(Trip $trip)
    {
        $trip->load([
            'order.client',
            'vehicle',
            'driver',
            'route',
            'histories.user',
            'proofOfDelivery',
        ]);

        return response()->json(['data' => [
            'id' => $trip->id,
            'reference' => $trip->reference,
            'status' => $trip->status,
            'vehicle_plate_snapshot' => $trip->vehicle_plate_snapshot,
            'driver_name_snapshot' => $trip->driver_name_snapshot,
            'trailer_plate_snapshot' => $trip->trailer_plate_snapshot,
            'departure_time' => $trip->departure_time?->toDateTimeString(),
            'arrival_time' => $trip->arrival_time?->toDateTimeString(),
            'created_at' => $trip->created_at?->toDateTimeString(),
            'planned_distance_km' => $trip->planned_distance_km,
            'actual_distance_km' => $trip->actual_distance_km,
            'start_odometer' => $trip->start_odometer,
            'end_odometer' => $trip->end_odometer,
            'is_deviated' => (bool) $trip->is_deviated,
            'deviation_detected_at' => $trip->deviation_detected_at?->toDateTimeString(),
            'deviation_duration_minutes' => $trip->deviation_duration_minutes,
            'deviation_max_distance_meters' => $trip->deviation_max_distance_meters,
            'order' => $trip->order ? [
                'id' => $trip->order->id,
                'reference' => $trip->order->reference,
                'client_name' => $trip->order->client?->name,
                'origin' => $trip->order->origin,
                'destination' => $trip->order->destination,
                'pickup_date' => $trip->order->pickup_date ?: null,
                'status' => $trip->order->status,
                'price' => $trip->order->price,
                'weight_kg' => $trip->order->weight_kg,
                'notes' => $trip->order->notes,
            ] : null,
            'vehicle' => $trip->vehicle ? [
                'id' => $trip->vehicle->id,
                'plate_number' => $trip->vehicle->plate_number,
                'capacity' => $trip->vehicle->capacity,
            ] : null,
            'driver' => $trip->driver ? [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
            ] : null,
            'route' => $trip->route ? [
                'id' => $trip->route->id,
                'name' => $trip->route->name,
                'estimated_distance_km' => $trip->route->estimated_distance_km,
            ] : null,
            'histories' => $trip->histories->map(fn ($history) => [
                'id' => $history->id,
                'action' => $history->action,
                'changes' => $history->changes,
                'user_name' => $history->user?->name,
                'created_at' => $history->created_at?->toDateTimeString(),
            ])->values(),
            'proof_of_delivery' => $trip->proofOfDelivery ? [
                'id' => $trip->proofOfDelivery->id,
                'delivered_at' => $trip->proofOfDelivery->delivered_at?->toDateTimeString(),
                'received_by_name' => $trip->proofOfDelivery->received_by_name,
                'status' => $trip->proofOfDelivery->status,
                'photo_path' => $trip->proofOfDelivery->photo_path,
            ] : null,
        ]]);
    }

    private function tripListItem(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'reference' => $trip->reference,
            'status' => $trip->status,
            'vehicle_plate_snapshot' => $trip->vehicle_plate_snapshot,
            'driver_name_snapshot' => $trip->driver_name_snapshot,
            'trailer_plate_snapshot' => $trip->trailer_plate_snapshot,
            'departure_time' => $trip->departure_time?->toDateTimeString(),
            'arrival_time' => $trip->arrival_time?->toDateTimeString(),
            'created_at' => $trip->created_at?->toDateTimeString(),
            'planned_distance_km' => $trip->planned_distance_km,
            'actual_distance_km' => $trip->actual_distance_km,
            'is_deviated' => (bool) $trip->is_deviated,
            'order_reference' => $trip->order?->reference,
            'client_name' => $trip->order?->client?->name,
            'origin' => $trip->order?->origin,
            'destination' => $trip->order?->destination,
            'route_name' => $trip->route?->name,
        ];
    }
}
