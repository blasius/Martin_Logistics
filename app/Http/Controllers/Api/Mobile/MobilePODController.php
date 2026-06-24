<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProofOfDelivery;
use App\Models\Trip;
use App\Services\ProofOfDeliveryService;
use Illuminate\Http\Request;

class MobilePODController extends Controller
{
    public function __construct(private ProofOfDeliveryService $podService) {}

    public function submit(Request $request)
    {
        $user = $request->user();

        if (!$user->driver) {
            return response()->json(['message' => 'User is not registered as a driver.'], 403);
        }

        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'received_by_name' => 'required|string|max:255',
            'received_by_relation' => 'nullable|string|max:255',
            'signature_data' => 'nullable|string',
            'notes' => 'nullable|string',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
            'delivered_at' => 'nullable|date',
        ]);

        $trip = Trip::with('order')->findOrFail($validated['trip_id']);

        if ($trip->driver_id !== $user->driver->id) {
            return response()->json(['message' => 'Unauthorized access to this trip.'], 403);
        }

        $validated['order_id'] = $trip->order_id;
        $validated['submitted_by'] = $user->id;

        $pod = $this->podService->submit($validated, $request->file('photo'));

        return response()->json([
            'message' => 'Proof of delivery submitted successfully.',
            'pod' => $pod->load(['order', 'trip']),
        ], 201);
    }
}
