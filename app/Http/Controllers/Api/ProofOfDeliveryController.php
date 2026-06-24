<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProofOfDelivery;
use App\Services\ProofOfDeliveryService;
use Illuminate\Http\Request;

class ProofOfDeliveryController extends Controller
{
    public function __construct(private ProofOfDeliveryService $podService) {}

    public function index()
    {
        $pods = ProofOfDelivery::with(['order.client.user:id,name', 'trip:id,status', 'submitter:id,name'])
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

    public function downloadPdf(ProofOfDelivery $proofOfDelivery)
    {
        $pdf = $this->podService->generatePdf($proofOfDelivery);
        return $pdf->download('delivery-receipt-' . $proofOfDelivery->order?->reference . '.pdf');
    }
}
