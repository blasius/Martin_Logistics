<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(protected ReturnService $returnService) {}

    public function index(Request $request)
    {
        $client = $request->user()->client;
        if (!$client) {
            return response()->json(['message' => 'No client profile found'], 404);
        }

        return ReturnRequest::with('items')
            ->where('client_id', $client->id)
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $client = $request->user()->client;
        if (!$client) {
            return response()->json(['message' => 'No client profile found'], 404);
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string',
        ]);

        $validated['client_id'] = $client->id;
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        $return = $this->returnService->create($validated, $validated['items']);
        return response()->json($return, 201);
    }

    public function show(Request $request, ReturnRequest $returnRequest)
    {
        $client = $request->user()->client;
        if (!$client || $returnRequest->client_id !== $client->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $returnRequest->load([
            'items',
            'order:id,reference,origin,destination',
            'pickupTrip:id,reference,status',
            'creditNote:id,reference,total,status',
        ]);
    }
}
