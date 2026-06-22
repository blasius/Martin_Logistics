<?php

namespace App\Services;

use App\Models\PartRequest;
use App\Models\PartRequestApproval;
use Illuminate\Support\Facades\DB;

class PartRequestService
{
    const LEVELS = [
        1 => 'clerk',
        2 => 'workshop_manager',
        3 => 'logistics_manager',
        4 => 'ops_manager',
    ];

    const STATUS_MAP = [
        1 => 'pending_clerk',
        2 => 'pending_workshop_manager',
        3 => 'pending_logistics_manager',
        4 => 'pending_ops_manager',
    ];

    public function create(array $data): PartRequest
    {
        $data['requested_at'] = $data['requested_at'] ?? now();
        $data['status'] = 'pending_clerk';
        $data['current_approval_level'] = 1;

        return PartRequest::create($data);
    }

    public function approve(int $id, int $approverId, ?string $comment = null): PartRequest
    {
        return DB::transaction(function () use ($id, $approverId, $comment) {
            $pr = PartRequest::findOrFail($id);
            $level = $pr->current_approval_level;

            abort_unless(in_array($pr->status, ['pending_clerk', 'pending_workshop_manager', 'pending_logistics_manager', 'pending_ops_manager']), 422, 'Part request is not pending approval.');
            abort_unless(isset(self::LEVELS[$level]), 422, 'Invalid approval level.');

            PartRequestApproval::create([
                'part_request_id' => $pr->id,
                'approver_id' => $approverId,
                'approval_level' => self::LEVELS[$level],
                'action' => 'approved',
                'comment' => $comment,
            ]);

            $nextLevel = $level + 1;

            if (isset(self::STATUS_MAP[$nextLevel])) {
                $pr->update([
                    'current_approval_level' => $nextLevel,
                    'status' => self::STATUS_MAP[$nextLevel],
                ]);
            } else {
                $pr->update([
                    'status' => 'approved',
                ]);
            }

            return $pr->fresh();
        });
    }

    public function reject(int $id, int $rejectedBy, string $reason): PartRequest
    {
        return DB::transaction(function () use ($id, $rejectedBy, $reason) {
            $pr = PartRequest::findOrFail($id);
            $level = $pr->current_approval_level;

            abort_unless(in_array($pr->status, ['pending_clerk', 'pending_workshop_manager', 'pending_logistics_manager', 'pending_ops_manager']), 422, 'Part request is not pending approval.');

            PartRequestApproval::create([
                'part_request_id' => $pr->id,
                'approver_id' => $rejectedBy,
                'approval_level' => self::LEVELS[$level],
                'action' => 'rejected',
                'comment' => $reason,
            ]);

            $pr->update(['status' => 'rejected']);

            return $pr->fresh();
        });
    }
}
