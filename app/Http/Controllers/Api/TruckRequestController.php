<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TruckRequest;
use App\Models\Order;
use App\Services\TruckRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TruckRequestController extends Controller
{
    public function __construct(protected TruckRequestService $truckService) {}

    public function index(Request $request)
    {
        $query = TruckRequest::with([
            'order:id,reference',
            'salesPerson:id,name',
            'assignedVehicle:id,plate_number,make,model',
            'assignedTrailer:id,plate_number,make,model',
            'dispatcher:id,name',
            'trip:id,reference',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sales_person_id')) {
            $query->where('sales_person_id', $request->sales_person_id);
        }

        // Sales users see only their own
        if ($request->boolean('my_only') && auth()->user()) {
            $query->where('sales_person_id', auth()->id());
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function show(TruckRequest $truckRequest)
    {
        return $truckRequest->load([
            'order.client:id,user_id', 'order.client.user:id,name',
            'salesPerson:id,name',
            'assignedVehicle:id,plate_number,make,model,capacity_kg',
            'assignedTrailer:id,plate_number,make,model',
            'dispatcher:id,name',
            'trip.preparation',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'cargo_type' => 'required|string|max:255',
            'tonnage' => 'required|numeric|min:0',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'required|string|max:255',
            'expected_pickup_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after_or_equal:expected_pickup_date',
            'special_requirements' => 'nullable|string',
            'agreed_rate' => 'nullable|numeric|min:0',
            'client_reference' => 'nullable|string|max:255',
            'payment_status' => 'sometimes|in:pending,paid',
            'notes' => 'nullable|string',
        ]);

        $validated['reference'] = $this->truckService->generateReference();
        $validated['sales_person_id'] = auth()->id();
        $validated['status'] = 'submitted';

        return TruckRequest::create($validated)->load([
            'order:id,reference',
            'salesPerson:id,name',
        ]);
    }

    public function update(Request $request, TruckRequest $truckRequest)
    {
        $validated = $request->validate([
            'cargo_type' => 'sometimes|string|max:255',
            'tonnage' => 'sometimes|numeric|min:0',
            'pickup_location' => 'sometimes|string|max:255',
            'dropoff_location' => 'sometimes|string|max:255',
            'expected_pickup_date' => 'sometimes|date',
            'expected_delivery_date' => 'sometimes|date|after_or_equal:expected_pickup_date',
            'special_requirements' => 'nullable|string',
            'agreed_rate' => 'nullable|numeric|min:0',
            'client_reference' => 'nullable|string|max:255',
            'payment_status' => 'sometimes|in:pending,paid',
            'status' => 'sometimes|in:draft,submitted,truck_assigned,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $truckRequest->update($validated);

        return $truckRequest->fresh()->load([
            'order:id,reference',
            'salesPerson:id,name',
        ]);
    }

    public function destroy(TruckRequest $truckRequest)
    {
        $truckRequest->delete();
        return response()->json(['message' => 'Truck request deleted']);
    }

    public function assign(Request $request, TruckRequest $truckRequest)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'trailer_id' => 'nullable|exists:vehicles,id',
        ]);

        $result = $this->truckService->assignAndCreateTrip(
            $truckRequest,
            $validated['vehicle_id'],
            $validated['trailer_id'] ?? null
        );

        return $result->load([
            'assignedVehicle:id,plate_number,make,model',
            'assignedTrailer:id,plate_number,make,model',
            'dispatcher:id,name',
            'trip:id,reference,status',
        ]);
    }

    public function availableVehicles(TruckRequest $truckRequest)
    {
        return response()->json($this->truckService->getAvailableVehicles($truckRequest));
    }

    public function queue(Request $request)
    {
        $query = TruckRequest::with([
            'order:id,reference,client_id',
            'order.client:id,user_id', 'order.client.user:id,name',
            'salesPerson:id,name',
        ])->whereIn('status', ['submitted', 'truck_assigned'])
            ->orderBy('expected_pickup_date', 'asc');

        return $query->paginate($request->per_page ?? 50);
    }
}
