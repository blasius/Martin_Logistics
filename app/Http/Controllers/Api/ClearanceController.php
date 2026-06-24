<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClearanceBypassRequest;
use App\Models\Trip;
use App\Services\ClearanceService;
use Illuminate\Http\Request;

class ClearanceController extends Controller
{
    public function __construct(protected ClearanceService $clearanceService) {}

    public function check(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        $checks = $this->clearanceService->check(
            $validated['vehicle_id'],
            $validated['driver_id'] ?? null
        );

        // Find pending bypasses for these check names
        $pendingBypasses = ClearanceBypassRequest::whereIn('check_name', collect($checks)->pluck('check'))
            ->where('status', 'pending')
            ->get()
            ->keyBy('check_name');

        $results = [];
        foreach ($checks as $check) {
            $result = $check;
            $result['bypass_pending'] = isset($pendingBypasses[$check['check']]);
            $result['bypass_id'] = $result['bypass_pending'] ? $pendingBypasses[$check['check']]->id : null;
            $results[] = $result;
        }

        return response()->json([
            'checks' => $results,
            'is_clear' => $this->clearanceService->isClear($checks),
        ]);
    }

    public function requestBypass(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'nullable|exists:trips,id',
            'check_name' => 'required|string',
            'check_label' => 'required|string',
            'reason' => 'required|string|min:10',
        ]);

        $bypass = ClearanceBypassRequest::create([
            'trip_id' => $validated['trip_id'] ?? null,
            'check_name' => $validated['check_name'],
            'check_label' => $validated['check_label'],
            'reason' => $validated['reason'],
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return $bypass->load('requester:id,name');
    }

    public function bypassRequests(Request $request)
    {
        $query = ClearanceBypassRequest::with([
            'trip:id,reference',
            'requester:id,name',
            'approver:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function approveBypass(Request $request, ClearanceBypassRequest $bypass)
    {
        if ($bypass->status !== 'pending') {
            return response()->json(['message' => 'Bypass request already processed'], 422);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $bypass->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'manager_comment' => $validated['comment'] ?? null,
        ]);

        return $bypass->load(['requester:id,name', 'approver:id,name']);
    }

    public function rejectBypass(Request $request, ClearanceBypassRequest $bypass)
    {
        if ($bypass->status !== 'pending') {
            return response()->json(['message' => 'Bypass request already processed'], 422);
        }

        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $bypass->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'manager_comment' => $validated['comment'],
        ]);

        return $bypass->load(['requester:id,name', 'approver:id,name']);
    }
}
