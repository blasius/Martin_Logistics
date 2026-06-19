<?php

namespace App\Services;

use App\Models\RepairRequest;
use App\Models\RepairAssignment;
use App\Models\RepairRelease;
use App\Models\Approval;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class RepairRequestService
{
    public function submit(RepairRequest $repairRequest): RepairRequest
    {
        $repairRequest->update([
            'status' => 'pending_approval',
            'submitted_at' => now(),
        ]);

        return $repairRequest->fresh();
    }

    public function approve(RepairRequest $repairRequest, int $approverId, string $approverRole, int $stage, ?string $comment = null): RepairRequest
    {
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

        if ($stage === 2 || ($stage === 1 && $repairRequest->items()->whereNull('part_id')->exists())) {
            $repairRequest->update(['status' => 'approved']);
        }

        return $repairRequest->fresh();
    }

    public function reject(RepairRequest $repairRequest, int $approverId, string $approverRole, int $stage, ?string $comment = null): RepairRequest
    {
        Approval::create([
            'approvable_type' => RepairRequest::class,
            'approvable_id' => $repairRequest->id,
            'approver_id' => $approverId,
            'approver_role' => $approverRole,
            'stage' => $stage,
            'status' => 'rejected',
            'comment' => $comment,
            'decided_at' => now(),
        ]);

        $repairRequest->update(['status' => 'draft']);

        return $repairRequest->fresh();
    }

    public function assignMechanic(RepairRequest $repairRequest, int $mechanicId): RepairAssignment
    {
        $assignment = RepairAssignment::create([
            'repair_request_id' => $repairRequest->id,
            'mechanic_id' => $mechanicId,
            'assigned_at' => now(),
        ]);

        $repairRequest->update(['status' => 'in_progress']);

        return $assignment;
    }

    public function startWork(int $assignmentId): RepairAssignment
    {
        $assignment = RepairAssignment::findOrFail($assignmentId);
        $assignment->update(['started_at' => now()]);

        return $assignment->fresh();
    }

    public function completeWork(int $assignmentId): RepairAssignment
    {
        $assignment = RepairAssignment::findOrFail($assignmentId);
        $assignment->update(['completed_at' => now()]);

        $assignment->repairRequest->update(['status' => 'completed']);

        return $assignment->fresh();
    }

    public function release(
        RepairRequest $repairRequest,
        int $releasedBy,
        ?string $unresolvedIssues,
        bool $checklistCompleted,
        ?float $odometer = null
    ): RepairRelease {
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
}
