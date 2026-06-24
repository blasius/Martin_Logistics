<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ServiceQueue;
use App\Models\YardEntry;
use App\Services\YardService;
use Illuminate\Http\Request;

class MobileYardController extends Controller
{
    public function __construct(protected YardService $yardService) {}

    public function checkIn(Request $request)
    {
        $user = $request->user();

        if (!$user->driver) {
            return response()->json(['message' => 'User is not registered as a driver.'], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_type' => 'required|in:offload,wash,workshop,fuel,loading_dock,unload_dock,fueling_bay,car_wash',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $coordinates = null;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $coordinates = ['lat' => (float) $validated['latitude'], 'lng' => (float) $validated['longitude']];
        }

        $result = $this->yardService->mobileCheckIn(
            $validated['vehicle_id'],
            $user->driver->id,
            $validated['service_type'],
            $coordinates
        );

        return response()->json([
            'message' => 'Checked in successfully.',
            'position' => $result['queue_entry']->position,
            'estimated_wait' => $this->yardService->estimatedWaitTime($validated['service_type']),
        ], 201);
    }

    public function myQueue(Request $request)
    {
        $user = $request->user();

        if (!$user->driver) {
            return response()->json(['message' => 'User is not registered as a driver.'], 403);
        }

        $entries = ServiceQueue::with(['vehicle:id,plate_number,make,model'])
            ->whereHas('vehicle', function ($q) use ($user) {
                $q->whereHas('currentDriver', function ($q2) use ($user) {
                    $q2->where('id', $user->driver->id);
                })->orWhere('id', $user->driver->vehicle_id);
            })
            ->whereIn('status', ['queued', 'in_progress'])
            ->orderBy('position')
            ->get()
            ->map(function ($entry) {
                $entry->estimated_wait = $this->yardService->estimatedWaitTime($entry->service_type);
                return $entry;
            });

        return response()->json($entries);
    }

    public function checkOut(Request $request)
    {
        $user = $request->user();

        if (!$user->driver) {
            return response()->json(['message' => 'User is not registered as a driver.'], 403);
        }

        $validated = $request->validate([
            'yard_entry_id' => 'required|exists:yard_entries,id',
        ]);

        $entry = YardEntry::findOrFail($validated['yard_entry_id']);

        abort_if($entry->driver_id !== $user->driver->id, 403, 'Unauthorized.');

        $this->yardService->checkOut($entry->id);

        return response()->json(['message' => 'Checked out successfully.']);
    }
}
