<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\RepairAssignment;
use App\Models\RepairRequest;
use App\Services\RepairRequestService;
use Illuminate\Http\Request;

class MobileWorkshopController extends Controller
{
    public function __construct(
        protected RepairRequestService $repairRequestService
    ) {}

    public function myTasks(Request $request)
    {
        $user = $request->user();

        $assignments = RepairAssignment::where('mechanic_id', $user->id)
            ->with([
                'repairRequest.vehicle:id,plate_number',
                'repairRequest.items.part:id,name,sku',
            ])
            ->orderByRaw("FIELD(status, 'assigned', 'in_progress', 'completed')")
            ->latest('assigned_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'repair_request_id' => $a->repair_request_id,
                'reference' => $a->repairRequest->reference,
                'vehicle_plate' => $a->repairRequest->vehicle?->plate_number,
                'instructions' => $a->instructions,
                'status' => $a->status,
                'duration' => $a->duration,
                'assigned_at' => $a->assigned_at,
                'started_at' => $a->started_at,
                'completed_at' => $a->completed_at,
                'completed_note' => $a->completed_note,
            ]);

        return response()->json($assignments);
    }

    public function taskDetail(RepairAssignment $assignment)
    {
        $this->authorizeMechanic($assignment);

        $assignment->load([
            'repairRequest.vehicle:id,plate_number,status',
            'repairRequest.items.part:id,name,sku,unit_of_measure',
            'repairRequest.driver:id,name,phone',
        ]);

        return response()->json([
            'assignment' => [
                'id' => $assignment->id,
                'instructions' => $assignment->instructions,
                'status' => $assignment->status,
                'duration' => $assignment->duration,
                'assigned_at' => $assignment->assigned_at,
                'started_at' => $assignment->started_at,
                'completed_at' => $assignment->completed_at,
                'completed_note' => $assignment->completed_note,
            ],
            'repair_request' => [
                'reference' => $assignment->repairRequest->reference,
                'type' => $assignment->repairRequest->type,
                'priority' => $assignment->repairRequest->priority,
                'description' => $assignment->repairRequest->description,
                'status' => $assignment->repairRequest->status,
                'vehicle' => $assignment->repairRequest->vehicle,
                'driver' => $assignment->repairRequest->driver,
                'items' => $assignment->repairRequest->items->map(fn ($i) => [
                    'description' => $i->description,
                    'part' => $i->part ? ['name' => $i->part->name, 'sku' => $i->part->sku] : null,
                    'estimated_quantity' => $i->estimated_quantity,
                    'estimated_unit_price' => $i->estimated_unit_price,
                ]),
            ],
        ]);
    }

    public function startWork(RepairAssignment $assignment)
    {
        $this->authorizeMechanic($assignment);

        if ($assignment->status !== 'assigned') {
            return response()->json(['message' => 'Task is not in assignable status.'], 422);
        }

        $result = $this->repairRequestService->startWork($assignment->id);

        return response()->json([
            'message' => 'Work started.',
            'assignment' => [
                'id' => $result->id,
                'status' => $result->status,
                'started_at' => $result->started_at,
            ],
        ]);
    }

    public function completeWork(Request $request, RepairAssignment $assignment)
    {
        $this->authorizeMechanic($assignment);

        if ($assignment->status !== 'in_progress') {
            return response()->json(['message' => 'Task is not in progress.'], 422);
        }

        $validated = $request->validate([
            'completed_note' => 'nullable|string',
        ]);

        $result = $this->repairRequestService->completeWork($assignment->id, $validated['completed_note']);

        return response()->json([
            'message' => 'Work completed.',
            'assignment' => [
                'id' => $result->id,
                'status' => $result->status,
                'completed_at' => $result->completed_at,
            ],
        ]);
    }

    protected function authorizeMechanic(RepairAssignment $assignment): void
    {
        abort_if($assignment->mechanic_id !== request()->user()->id, 403, 'This task is not assigned to you.');
    }
}
