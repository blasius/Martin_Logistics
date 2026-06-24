<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\DispatchPreparationService;
use Illuminate\Http\Request;

class DispatchPreparationController extends Controller
{
    public function __construct(protected DispatchPreparationService $prepService) {}

    public function show(Trip $trip)
    {
        return $this->prepService->getOrCreate($trip->id)
            ->load(['trip.vehicle', 'preparedBy:id,name']);
    }

    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'fuel_confirmed' => 'boolean',
            'fuel_liters' => 'nullable|numeric|min:0',
            'odometer_start' => 'nullable|numeric|min:0',
            'documents_uploaded' => 'boolean',
            'instructions_provided' => 'boolean',
            'inspection_confirmed' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        return $this->prepService->update($trip->id, $validated)
            ->load(['trip.vehicle', 'preparedBy:id,name']);
    }

    public function markReady(Trip $trip)
    {
        try {
            return $this->prepService->markReady($trip->id)
                ->load(['trip.vehicle', 'preparedBy:id,name']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function needsPreparation()
    {
        return response()->json($this->prepService->needsPreparation());
    }

    public function readyToDepart()
    {
        return response()->json($this->prepService->readyToDepart());
    }

    public function dispatcherTrips()
    {
        $trips = Trip::with(['vehicle:id,plate_number,make,model', 'preparation', 'truckRequest'])
            ->where('dispatcher_id', auth()->id())
            ->orderByRaw("FIELD(status, 'pre_departure', 'assigned', 'on_route')")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($trips);
    }
}
