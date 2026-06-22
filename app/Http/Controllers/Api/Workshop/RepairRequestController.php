<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use App\Models\Vehicle;
use App\Models\User;
use App\Services\RepairRequestService;
use App\Services\YardService;
use App\Notifications\RepairRequestCreated;
use Illuminate\Http\Request;

class RepairRequestController extends Controller
{
    public function __construct(
        protected RepairRequestService $rrService,
        protected YardService $yardService
    ) {}

    public function index(Request $request)
    {
        $query = RepairRequest::with([
            'vehicle:id,plate_number',
            'mechanic:id,name',
            'driver:id,name',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhereHas('vehicle', fn($v) => $v->where('plate_number', 'like', "%{$request->search}%"));
            });
        }

        $query->orderByRaw("FIELD(status, 'draft', 'pending_approval', 'pending_ops_approval', 'approved', 'in_progress', 'completed', 'released', 'cancelled')")
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')");

        return response()->json([
            'repair_requests' => $query->paginate($request->per_page ?? 15),
            'stats' => [
                'total' => RepairRequest::count(),
                'pending_approval' => RepairRequest::whereIn('status', ['pending_approval', 'pending_ops_approval'])->count(),
                'in_progress' => RepairRequest::where('status', 'in_progress')->count(),
                'completed' => RepairRequest::where('status', 'completed')->count(),
                'released' => RepairRequest::where('status', 'released')->count(),
                'critical' => RepairRequest::where('priority', 'critical')->whereNotIn('status', ['released', 'cancelled'])->count(),
            ],
        ]);
    }

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
        ]);

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $inYard = $this->yardService->isVehicleInYardByCoordinates(
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );
            abort_unless($inYard, 422, 'Vehicle is not inside the yard. Service requests can only be submitted from within the yard.');
        }

        if ($driverId = $validated['driver_id'] ?? null) {
            $active = RepairRequest::where('driver_id', $driverId)
                ->whereNotIn('status', ['released', 'cancelled'])
                ->exists();
            abort_if($active, 422, 'Driver already has an active repair request. Complete or cancel it before creating a new one.');
        }

        $rr = RepairRequest::create([
            'reference' => RepairRequestService::generateReference(),
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => $validated['driver_id'],
            'mechanic_id' => null,
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'status' => 'draft',
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $item['estimated_total'] = ($item['estimated_quantity'] ?? 0) * ($item['estimated_unit_price'] ?? 0);
                $rr->items()->create($item);
            }
        }

        $isPortal = !$request->filled('latitude') && !$request->filled('longitude');

        if ($isPortal && $rr->driver) {
            $rr->driver->notify(new RepairRequestCreated($rr));
        }

        return $rr->load(['vehicle:id,plate_number', 'items.part:id,name,sku']);
    }

    public function show(RepairRequest $repairRequest)
    {
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

    public function submit(RepairRequest $repairRequest)
    {
        $rr = $this->rrService->submit($repairRequest);

        return $rr->load(['vehicle:id,plate_number', 'items']);
    }

    public function requestApproval(Request $request, RepairRequest $repairRequest)
    {
        $rr = $this->rrService->requestApproval($repairRequest, $request->user()->id);

        return $rr->load(['vehicle:id,plate_number', 'assignments.mechanic:id,name']);
    }

    public function approve(Request $request, RepairRequest $repairRequest)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $user = $request->user();
        $role = $user->getRoleNames()->first() ?? 'dispatcher';

        $rr = $this->rrService->approve($repairRequest, $user->id, $role, $validated['comment'] ?? null);

        return $rr->load(['vehicle:id,plate_number', 'approvals']);
    }

    public function reject(Request $request, RepairRequest $repairRequest)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $user = $request->user();
        $role = $user->getRoleNames()->first() ?? 'dispatcher';

        $rr = $this->rrService->reject($repairRequest, $user->id, $role, $validated['comment'] ?? null);

        return $rr->load(['vehicle:id,plate_number', 'approvals']);
    }

    public function assignMechanic(Request $request, RepairRequest $repairRequest)
    {
        $validated = $request->validate([
            'mechanic_id' => 'required|exists:users,id',
            'instructions' => 'nullable|string',
        ]);

        $assignment = $this->rrService->assignMechanic(
            $repairRequest,
            $validated['mechanic_id'],
            $validated['instructions'] ?? null
        );

        return $assignment->load('mechanic:id,name');
    }

    public function reassignMechanic(Request $request, RepairRequest $repairRequest)
    {
        $validated = $request->validate([
            'mechanic_id' => 'required|exists:users,id',
            'instructions' => 'nullable|string',
        ]);

        $assignment = $this->rrService->reassignMechanic(
            $repairRequest,
            $validated['mechanic_id'],
            $validated['instructions'] ?? null
        );

        return $assignment->load('mechanic:id,name');
    }

    public function startWork(int $assignmentId)
    {
        $assignment = $this->rrService->startWork($assignmentId);

        return $assignment->load('mechanic:id,name');
    }

    public function completeWork(Request $request, int $assignmentId)
    {
        $validated = $request->validate([
            'completed_note' => 'nullable|string',
        ]);

        $assignment = $this->rrService->completeWork($assignmentId, $validated['completed_note'] ?? null);

        return $assignment->load('mechanic:id,name');
    }

    public function useParts(Request $request, RepairRequest $repairRequest)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'part_id' => 'required|exists:parts,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $this->rrService->useParts($repairRequest, $validated['warehouse_id'], $validated['part_id'], $validated['quantity'], $request->user()->id);

        return response()->json(['message' => 'Parts consumed']);
    }

    public function release(Request $request, RepairRequest $repairRequest)
    {
        $validated = $request->validate([
            'unresolved_issues' => 'nullable|string',
            'checklist_completed' => 'required|boolean',
            'odometer_at_release' => 'nullable|numeric',
        ]);

        $release = $this->rrService->release(
            $repairRequest,
            $request->user()->id,
            $validated['unresolved_issues'] ?? null,
            $validated['checklist_completed'],
            $validated['odometer_at_release'] ?? null
        );

        return $release->load('releasedBy:id,name');
    }

    public function cancel(RepairRequest $repairRequest)
    {
        $rr = $this->rrService->cancel($repairRequest);

        return $rr->load(['vehicle:id,plate_number']);
    }

    public function mechanics()
    {
        return User::role('mechanic')->get(['id', 'name', 'email']);
    }

    public function vehicles()
    {
        return Vehicle::with('latestDriverAssignment.driver:id,name')
            ->get(['id', 'plate_number', 'status'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'status' => $v->status,
                'current_driver' => $v->latestDriverAssignment?->driver?->name,
                'current_driver_id' => $v->latestDriverAssignment?->driver_id,
            ]);
    }

    public function searchVehicles(Request $request)
    {
        $q = $request->query('q');

        return Vehicle::when($q, fn ($query) => $query->where('plate_number', 'like', "%{$q}%"))
            ->with('latestDriverAssignment.driver:id,name')
            ->limit(10)
            ->get(['id', 'plate_number', 'status'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'status' => $v->status,
                'current_driver' => $v->latestDriverAssignment?->driver?->name,
                'current_driver_id' => $v->latestDriverAssignment?->driver_id,
            ]);
    }
}
