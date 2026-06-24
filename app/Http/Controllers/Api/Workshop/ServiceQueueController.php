<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Services\YardService;
use App\Models\ServiceQueue;
use Illuminate\Http\Request;

class ServiceQueueController extends Controller
{
    public function __construct(protected YardService $yardService) {}

    public function index(Request $request)
    {
        $query = ServiceQueue::with(['vehicle:id,plate_number,make,model', 'submitter:id,name']);

        if ($request->service_type) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return $query->orderBy('position')->orderBy('entered_at')
            ->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_type' => 'required|in:offload,wash,workshop,fuel',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $coordinates = null;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $coordinates = ['lat' => (float) $validated['latitude'], 'lng' => (float) $validated['longitude']];
        }

        $entry = $this->yardService->enqueue(
            $validated['vehicle_id'],
            $validated['service_type'],
            $request->user()->id,
            $coordinates
        );

        return $entry->load(['vehicle:id,plate_number,make,model', 'submitter:id,name']);
    }

    public function start(ServiceQueue $serviceQueue)
    {
        return $this->yardService->startService($serviceQueue->id);
    }

    public function complete(ServiceQueue $serviceQueue)
    {
        return $this->yardService->completeService($serviceQueue->id);
    }

    public function skip(ServiceQueue $serviceQueue)
    {
        return $this->yardService->skip($serviceQueue->id);
    }

    public function reorder(Request $request, ServiceQueue $serviceQueue)
    {
        $validated = $request->validate([
            'position' => 'required|integer|min:1',
        ]);

        $this->yardService->reorder($serviceQueue->id, $validated['position']);

        return response()->json(['message' => 'Queue reordered']);
    }

    public function stats()
    {
        return response()->json($this->yardService->queueStats());
    }
}
