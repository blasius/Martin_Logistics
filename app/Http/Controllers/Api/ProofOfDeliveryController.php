<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProofOfDelivery;
use App\Services\ProofOfDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProofOfDeliveryController extends Controller
{
    public function __construct(private ProofOfDeliveryService $podService) {}

    public function index(Request $request)
    {
        $pods = ProofOfDelivery::with(['order.client.user:id,name', 'trip:id,status', 'submitter:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('trip_id'), fn ($q) => $q->where('trip_id', $request->trip_id))
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($pod) {
                $pod->order->client_name = $pod->order?->client?->user?->name;
                return $pod;
            });
        return response()->json($pods);
    }

    public function show(ProofOfDelivery $proofOfDelivery)
    {
        $proofOfDelivery->load(['order.client.user:id,name', 'trip.vehicle', 'trip.driver.user:id,name', 'submitter:id,name']);
        return response()->json($proofOfDelivery);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'trip_id' => 'nullable|exists:trips,id',
            'received_by_name' => 'required|string|max:255',
            'received_by_relation' => 'nullable|string|max:255',
            'signature_data' => 'nullable|string',
            'notes' => 'nullable|string',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
            'delivered_at' => 'nullable|date',
        ]);

        if ($request->hasFile('photo')) {
            $request->validate(['photo' => 'image|max:5120']);
        }

        $pod = $this->podService->submit($validated, $request->file('photo'));

        return response()->json(['message' => 'Proof of delivery submitted', 'pod' => $pod], 201);
    }

    public function update(Request $request, ProofOfDelivery $proofOfDelivery)
    {
        $validated = $request->validate([
            'received_by_name' => 'sometimes|string|max:255',
            'received_by_relation' => 'nullable|string|max:255',
            'signature_data' => 'nullable|string',
            'notes' => 'nullable|string',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
            'delivered_at' => 'nullable|date',
            'status' => 'sometimes|in:draft,submitted,confirmed',
        ]);

        if ($request->hasFile('photo')) {
            $request->validate(['photo' => 'image|max:5120']);
            if ($proofOfDelivery->photo_path) {
                Storage::disk('public')->delete($proofOfDelivery->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('pod-photos', 'public');
        }

        $proofOfDelivery->update($validated);

        return response()->json(['message' => 'Proof of delivery updated', 'pod' => $proofOfDelivery->fresh()->load(['order', 'trip', 'submitter'])]);
    }

    public function confirm(ProofOfDelivery $proofOfDelivery)
    {
        $pod = $this->podService->confirm($proofOfDelivery);
        return response()->json(['message' => 'Proof of delivery confirmed', 'pod' => $pod]);
    }

    public function reject(Request $request, ProofOfDelivery $proofOfDelivery)
    {
        if ($proofOfDelivery->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted proofs of delivery can be rejected.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $pod = $this->podService->reject($proofOfDelivery, $validated['reason']);
        return response()->json(['message' => 'Proof of delivery rejected. Driver can re-submit.', 'pod' => $pod]);
    }

    public function downloadPdf(ProofOfDelivery $proofOfDelivery)
    {
        $pdf = $this->podService->generatePdf($proofOfDelivery);
        return $pdf->download('delivery-receipt-' . $proofOfDelivery->order?->reference . '.pdf');
    }
}
