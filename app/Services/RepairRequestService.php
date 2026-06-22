<?php

namespace App\Services;

use App\Models\RepairRequest;
use App\Models\RepairAssignment;
use App\Models\RepairRelease;
use App\Models\Approval;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Notifications\MechanicAssigned;
use Illuminate\Support\Facades\DB;

class RepairRequestService
{
    public function submit(RepairRequest $repairRequest): RepairRequest
    {
        $hasParts = $repairRequest->items()->whereNotNull('part_id')->exists();

        $repairRequest->update([
            'status' => $hasParts ? 'pending_approval' : 'approved',
            'submitted_at' => now(),
        ]);

        return $repairRequest->fresh();
    }

    public function requestApproval(RepairRequest $repairRequest, int $userId): RepairRequest
    {
        abort_if($repairRequest->approval_requested_at, 422, 'Approval already requested for this repair.');
        abort_if($repairRequest->status !== 'pending_approval', 422, 'Repair request is not in a pending approval state.');

        $hasMechanic = $repairRequest->assignments()->exists();
        abort_unless($hasMechanic, 422, 'A mechanic must be assigned before requesting approval.');

        $repairRequest->update([
            'approval_requested_at' => now(),
            'approval_requested_by' => $userId,
        ]);

        return $repairRequest->fresh();
    }

    public function approve(RepairRequest $repairRequest, int $approverId, string $approverRole, ?string $comment = null): RepairRequest
    {
        abort_unless($repairRequest->approval_requested_at, 422, 'Approval has not been requested for this repair.');

        $roleLower = strtolower($approverRole);
        $isAdmin = in_array($roleLower, ['super_admin', 'admin']);
        $isLogistics = $isAdmin || str_contains($roleLower, 'logistics');
        $isOps = $isAdmin || str_contains($roleLower, 'operations');
        $isFirstLevel = $repairRequest->status === 'pending_approval';
        $isSecondLevel = $repairRequest->status === 'pending_ops_approval';

        abort_unless($isFirstLevel || $isSecondLevel, 422, 'Repair request is not in an approvable state.');

        if ($isFirstLevel) {
            abort_unless($isLogistics, 403, 'Only Logistics Manager (or Admin) can give first-level approval.');
            $nextStatus = 'pending_ops_approval';
            $stage = 1;
        } else {
            abort_unless($isOps, 403, 'Only Operations Manager (or Admin) can give second-level approval.');
            $nextStatus = 'approved';
            $stage = 2;
        }

        Approval::create([
            'approvable_type' => RepairRequest::class,
            'approvable_id' => $repairRequest->id,
            'approver_id' => $approverId,
            'approver_role' => $approverRole,
            'stage' => $stage,
            'status' => 'approved',
            'comment' => $comment,
            'decided_at' => now(),
        ]);

        $repairRequest->update(['status' => $nextStatus]);

        if ($nextStatus === 'approved') {
            $this->notifyAssignedMechanics($repairRequest);
        }

        return $repairRequest->fresh();
    }

    public function reject(RepairRequest $repairRequest, int $approverId, string $approverRole, ?string $comment = null): RepairRequest
    {
        abort_unless(in_array($repairRequest->status, ['pending_approval', 'pending_ops_approval']), 422, 'Repair request is not in an approvable state.');

        Approval::create([
            'approvable_type' => RepairRequest::class,
            'approvable_id' => $repairRequest->id,
            'approver_id' => $approverId,
            'approver_role' => $approverRole,
            'stage' => $repairRequest->status === 'pending_ops_approval' ? 2 : 1,
            'status' => 'rejected',
            'comment' => $comment,
            'decided_at' => now(),
        ]);

        $repairRequest->update(['status' => 'draft']);

        return $repairRequest->fresh();
    }

    public function assignMechanic(RepairRequest $repairRequest, int $mechanicId, ?string $instructions = null): RepairAssignment
    {
        $assignment = RepairAssignment::create([
            'repair_request_id' => $repairRequest->id,
            'mechanic_id' => $mechanicId,
            'instructions' => $instructions,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        if ($repairRequest->status === 'approved') {
            $repairRequest->update(['status' => 'in_progress']);
            $this->notifyMechanic($mechanicId, $repairRequest, $assignment);
        }

        return $assignment;
    }

    public function startWork(int $assignmentId): RepairAssignment
    {
        $assignment = RepairAssignment::findOrFail($assignmentId);
        $assignment->update(['started_at' => now(), 'status' => 'in_progress']);

        return $assignment->fresh();
    }

    public function completeWork(int $assignmentId, ?string $completedNote = null): RepairAssignment
    {
        $assignment = RepairAssignment::findOrFail($assignmentId);
        $assignment->update([
            'completed_at' => now(),
            'status' => 'completed',
            'completed_note' => $completedNote,
        ]);

        $allDone = $assignment->repairRequest->assignments()
            ->where('status', '!=', 'completed')
            ->doesntExist();

        if ($allDone) {
            $assignment->repairRequest->update(['status' => 'completed']);
        }

        return $assignment->fresh();
    }

    public function reassignMechanic(RepairRequest $repairRequest, int $mechanicId, ?string $instructions = null): RepairAssignment
    {
        $assignment = RepairAssignment::create([
            'repair_request_id' => $repairRequest->id,
            'mechanic_id' => $mechanicId,
            'instructions' => $instructions,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $repairRequest->update(['status' => 'in_progress']);
        $this->notifyMechanic($mechanicId, $repairRequest, $assignment);

        return $assignment;
    }

    public function release(
        RepairRequest $repairRequest,
        int $releasedBy,
        ?string $unresolvedIssues,
        bool $checklistCompleted,
        ?float $odometer = null
    ): RepairRelease {
        $allCompleted = $repairRequest->assignments()
            ->where('status', '!=', 'completed')
            ->doesntExist();

        abort_unless($allCompleted, 422, 'Cannot release: not all mechanics have completed their tasks.');

        $release = RepairRelease::create([
            'repair_request_id' => $repairRequest->id,
            'released_by' => $releasedBy,
            'released_at' => now(),
            'odometer_at_release' => $odometer,
            'unresolved_issues' => $unresolvedIssues,
            'checklist_completed' => $checklistCompleted,
        ]);

        $repairRequest->vehicle->update(['status' => 'released_from_workshop']);
        $repairRequest->update(['status' => 'released']);

        return $release;
    }

    public function useParts(RepairRequest $repairRequest, int $warehouseId, int $partId, float $quantity, int $userId): void
    {
        $stockLevel = StockLevel::where('warehouse_id', $warehouseId)
            ->where('part_id', $partId)
            ->first();

        if ($stockLevel && $stockLevel->quantity >= $quantity) {
            $stockLevel->decrement('quantity', $quantity);
        }

        StockMovement::create([
            'warehouse_id' => $warehouseId,
            'part_id' => $partId,
            'quantity' => -$quantity,
            'type' => 'out',
            'reference_type' => RepairRequest::class,
            'reference_id' => $repairRequest->id,
            'user_id' => $userId,
            'notes' => "Parts used for repair {$repairRequest->reference}",
        ]);
    }

    public function cancel(RepairRequest $repairRequest): RepairRequest
    {
        $repairRequest->update(['status' => 'cancelled']);

        return $repairRequest->fresh();
    }

    public static function generateReference(): string
    {
        $prefix = 'RR-';
        $date = now()->format('Ymd');
        $last = RepairRequest::where('reference', 'like', "{$prefix}{$date}-%")
            ->orderBy('id', 'desc')
            ->first();

        $seq = $last ? (int) substr($last->reference, -4) + 1 : 1;

        return "{$prefix}{$date}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    protected function notifyMechanic(int $mechanicId, RepairRequest $repairRequest, RepairAssignment $assignment): void
    {
        $mechanic = \App\Models\User::find($mechanicId);
        if ($mechanic) {
            $mechanic->notify(new MechanicAssigned($repairRequest, $assignment));
        }
    }

    protected function notifyAssignedMechanics(RepairRequest $repairRequest): void
    {
        foreach ($repairRequest->assignments as $assignment) {
            $this->notifyMechanic($assignment->mechanic_id, $repairRequest, $assignment);
        }
    }
}
