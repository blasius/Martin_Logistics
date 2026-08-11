<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\RepairRequest;
use App\Models\Vehicle;
use App\Services\RepairRequestService;
use App\Services\YardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileRepairRequestController extends Controller
{
    public function __construct(
        protected RepairRequestService $rrService,
        protected YardService $yardService
    ) {}

    /**
     * Vehicles assigned to the authenticated driver.
     */
    public function vehicles(Request $request)
    {
        $user = $request->user();

        return Vehicle::whereHas('latestDriverAssignment', fn ($q) => $q->where('driver_id', $user->id))
            ->with('latestDriverAssignment.driver:id,name')
            ->get(['id', 'plate_number', 'status'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'status' => $v->status,
                'current_driver' => $v->latestDriverAssignment?->driver?->name,
                'current_driver_id' => $v->latestDriverAssignment?->driver_id,
            ]);
    }

    /**
     * Searchable parts catalog for the create form.
     */
    public function parts(Request $request)
    {
        $q = $request->query('q');

        return Part::when($q, function ($query) use ($q) {
                $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%"));
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'sku', 'unit_price', 'unit_of_measure']);
    }

    /**
     * The driver's currently active repair request (not released/cancelled).
     */
    public function current(Request $request)
    {
        $user = $request->user();

        $rr = RepairRequest::where('driver_id', $user->id)
            ->whereNotIn('status', ['released', 'cancelled'])
            ->with(['vehicle:id,plate_number,status', 'items.part:id,name,sku'])
            ->latest()
            ->first();

        if (!$rr) {
            return response()->json(['message' => 'No active repair request.'], 404);
        }

        return response()->json($rr);
    }

    /**
     * The authenticated driver's repair requests.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $vehicleIds = DB::table('driver_vehicle_assignments')
            ->where('driver_id', $user->id)
            ->whereNull('end_date')
            ->pluck('vehicle_id');

        return RepairRequest::where('driver_id', $user->id)
            ->orWhereIn('vehicle_id', $vehicleIds)
            ->with([
                'vehicle:id,plate_number,status',
                'items.part:id,name,sku',
                'assignments.mechanic:id,name',
            ])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($q) use ($s) {
                    $q->where('reference', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);
    }

    /**
     * Create a repair request from the mobile driver app.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:users,id',
            'type' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'description' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.part_id' => 'nullable|exists:parts,id',
            'items.*.estimated_quantity' => 'nullable|numeric|min:0',
            'items.*.estimated_unit_price' => 'nullable|numeric|min:0',
            'photo_urls' => 'nullable|array',
            'photo_urls.*' => 'nullable|string',
        ]);

        $user = $request->user();
        $roles = $user->getRoleNames()->map(fn ($r) => strtolower($r));
        $isDriver = $user->driver !== null || $roles->contains('driver');
        $isStaff = $roles->intersect([
            'super_admin', 'admin', 'operator',
            'workshop manager', 'logistics manager', 'operations manager',
        ])->isNotEmpty();

        abort_unless($isDriver || $isStaff, 403, 'Only drivers and workshop staff can create repair requests.');

        if ($isDriver) {
            $assignedVehicleIds = DB::table('driver_vehicle_assignments')
                ->where('driver_id', $user->id)
                ->whereNull('end_date')
                ->pluck('vehicle_id');

            abort_unless(
                $assignedVehicleIds->contains($validated['vehicle_id']),
                403,
                'Vehicle is not assigned to you.'
            );
        }

        $coordinates = null;
        $geofenceVerified = false;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $coordinates = ['lat' => (float) $validated['latitude'], 'lng' => (float) $validated['longitude']];
            $geofenceVerified = $this->yardService->isVehicleInYardByCoordinates(
                $coordinates['lat'], $coordinates['lng']
            );
            abort_unless($geofenceVerified, 422, 'Vehicle is not inside the yard. Service requests can only be submitted from within the yard.');
        }

        $driverId = $isDriver ? $user->id : ($validated['driver_id'] ?? null);
        if ($driverId) {
            $active = RepairRequest::where('driver_id', $driverId)
                ->whereNotIn('status', ['released', 'cancelled'])
                ->exists();
            abort_if($active, 422, 'Driver already has an active repair request. Complete or cancel it before creating a new one.');
        }

        $rr = RepairRequest::create([
            'reference' => RepairRequestService::generateReference(),
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => $driverId,
            'mechanic_id' => null,
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'status' => 'draft',
            'coordinates' => $coordinates,
            'geofence_verified' => $geofenceVerified,
            'photo_urls' => $validated['photo_urls'] ?? null,
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $item['estimated_total'] = ($item['estimated_quantity'] ?? 0) * ($item['estimated_unit_price'] ?? 0);
                $rr->items()->create($item);
            }
        }

        return response()->json(
            $rr->load(['vehicle:id,plate_number,status', 'items.part:id,name,sku,unit_of_measure']),
            201
        );
    }

    /**
     * Show a repair request the user owns or is assigned to.
     */
    public function show(Request $request, RepairRequest $repairRequest)
    {
        $this->authorizeAccess($request->user(), $repairRequest);

        return $repairRequest->load([
            'vehicle:id,plate_number,status',
            'mechanic:id,name',
            'driver:id,name',
            'items.part:id,name,sku,unit_of_measure',
            'approvals',
            'assignments.mechanic:id,name',
            'release.releasedBy:id,name',
            'purchaseOrders:id,reference,status,total_amount',
            'approvalRequester:id,name',
        ]);
    }

    /**
     * Submit a draft for review.
     */
    public function submit(Request $request, RepairRequest $repairRequest)
    {
        $user = $request->user();
        abort_unless($repairRequest->driver_id === $user->id, 403, 'This repair request does not belong to you.');

        $rr = $this->rrService->submit($repairRequest);

        return response()->json($rr->load(['vehicle:id,plate_number', 'items']));
    }

    /**
     * Cancel a draft/pending repair request.
     */
    public function cancel(Request $request, RepairRequest $repairRequest)
    {
        $user = $request->user();
        abort_unless($repairRequest->driver_id === $user->id, 403, 'This repair request does not belong to you.');

        $rr = $this->rrService->cancel($repairRequest);

        return response()->json($rr->load(['vehicle:id,plate_number']));
    }

    protected function authorizeAccess($user, RepairRequest $repairRequest): void
    {
        $roles = $user->getRoleNames()->map(fn ($r) => strtolower($r));
        $isAdmin = $roles->intersect(['super_admin', 'admin'])->isNotEmpty();

        $assignedVehicleIds = DB::table('driver_vehicle_assignments')
            ->where('driver_id', $user->id)
            ->whereNull('end_date')
            ->pluck('vehicle_id');

        $isOwner = $repairRequest->driver_id === $user->id
            || $assignedVehicleIds->contains($repairRequest->vehicle_id);
        $isAssignedMechanic = $repairRequest->assignments()
            ->where('mechanic_id', $user->id)
            ->exists();

        abort_unless($isOwner || $isAssignedMechanic || $isAdmin, 403, 'You do not have access to this repair request.');
    }
}
