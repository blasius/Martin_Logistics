<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\PartRequest;
use App\Services\PartRequestService;
use Illuminate\Http\Request;

class PartRequestController extends Controller
{
    public function __construct(protected PartRequestService $prService) {}

    public function index(Request $request)
    {
        $query = PartRequest::with(['part:id,name', 'repairRequest:id,reference', 'requester:id,name']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->repair_request_id) {
            $query->where('repair_request_id', $request->repair_request_id);
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);
    }

    public function show(PartRequest $partRequest)
    {
        return $partRequest->load([
            'part:id,name,sku,unit_of_measure,unit_price',
            'repairRequest:id,reference',
            'requester:id,name',
            'approvals.approver:id,name',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'repair_request_id' => 'nullable|exists:repair_requests,id',
            'part_id' => 'required|exists:parts,id',
            'quantity' => 'required|integer|min:1',
            'urgency' => 'nullable|in:normal,urgent',
            'notes' => 'nullable|string',
        ]);

        $validated['requested_by'] = $request->user()->id;
        $validated['urgency'] = $validated['urgency'] ?? 'normal';

        $pr = $this->prService->create($validated);

        return $pr->load(['part:id,name', 'requester:id,name']);
    }

    public function approve(Request $request, PartRequest $partRequest)
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $pr = $this->prService->approve($partRequest->id, $request->user()->id, $validated['comment'] ?? null);

        return $pr->load(['part:id,name', 'requester:id,name', 'approvals.approver:id,name']);
    }

    public function reject(Request $request, PartRequest $partRequest)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $pr = $this->prService->reject($partRequest->id, $request->user()->id, $validated['reason']);

        return $pr->load(['part:id,name', 'requester:id,name', 'approvals.approver:id,name']);
    }
}
