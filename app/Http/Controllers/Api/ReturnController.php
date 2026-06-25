<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(protected ReturnService $returnService) {}

    public function index(Request $request)
    {
        $query = ReturnRequest::with([
            'order:id,reference',
            'client.user:id,name',
            'items',
            'creator:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        return $query->latest()->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'client_id' => 'required|exists:clients,id',
            'reason' => 'required|string',
            'pickup_address' => 'nullable|string',
            'pickup_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        $return = $this->returnService->create($validated, $validated['items']);

        return response()->json($return, 201);
    }

    public function show(ReturnRequest $returnRequest)
    {
        return $returnRequest->load([
            'order:id,reference,origin,destination',
            'client.user:id,name,email',
            'items',
            'pickupTrip:id,reference,status',
            'creditNote:id,reference,total,status',
            'creator:id,name',
            'approver:id,name',
        ]);
    }

    public function update(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate([
            'reason' => 'sometimes|string',
            'pickup_address' => 'nullable|string',
            'pickup_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array',
            'items.*.id' => 'nullable|exists:return_items,id',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.reason' => 'nullable|string',
        ]);

        $returnRequest->update($validated);

        if (isset($validated['items'])) {
            $incomingIds = collect($validated['items'])->pluck('id')->filter();
            $returnRequest->items()->whereNotIn('id', $incomingIds)->delete();
            foreach ($validated['items'] as $item) {
                if (isset($item['id'])) {
                    $returnRequest->items()->where('id', $item['id'])->update($item);
                } else {
                    $returnRequest->items()->create($item);
                }
            }
        }

        return $returnRequest->fresh()->load('items');
    }

    public function approve(ReturnRequest $returnRequest)
    {
        $return = $this->returnService->approve($returnRequest, auth()->id());
        return response()->json($return);
    }

    public function reject(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate(['reason' => 'nullable|string']);
        $return = $this->returnService->reject($returnRequest, $validated['reason'] ?? null);
        return response()->json($return);
    }

    public function schedulePickup(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate([
            'pickup_address' => 'required|string',
            'pickup_date' => 'required|date',
            'pickup_trip_id' => 'nullable|exists:trips,id',
        ]);

        $return = $this->returnService->schedulePickup(
            $returnRequest,
            $validated['pickup_address'],
            $validated['pickup_date'],
            $validated['pickup_trip_id'] ?? null
        );

        return response()->json($return);
    }

    public function receive(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate([
            'condition' => 'nullable|string',
            'item_conditions' => 'nullable|array',
            'item_conditions.*' => 'nullable|string',
        ]);

        $return = $this->returnService->receive(
            $returnRequest,
            $validated['condition'] ?? 'unknown',
            $validated['item_conditions'] ?? []
        );

        return response()->json($return);
    }

    public function complete(Request $request, ReturnRequest $returnRequest)
    {
        $validated = $request->validate([
            'disposition' => 'required|in:restock,repair,scrap,return_to_vendor,other',
            'credit_note_id' => 'nullable|exists:invoices,id',
        ]);

        $return = $this->returnService->complete(
            $returnRequest,
            $validated['disposition'],
            $validated['credit_note_id'] ?? null
        );

        return response()->json($return);
    }

    public function cancel(ReturnRequest $returnRequest)
    {
        $return = $this->returnService->cancel($returnRequest);
        return response()->json($return);
    }

    public function stats()
    {
        return response()->json($this->returnService->getStats());
    }
}
