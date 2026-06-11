<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripHistory;
use Illuminate\Http\Request;

class MobileTripController extends Controller
{
    /**
     * Get the driver's currently active trip.
     */
    public function current(Request $request)
    {
        $user = $request->user();

        // Ensure user has an associated driver profile
        if (!$user->driver) {
            return response()->json(['message' => 'User is not registered as a driver.'], 403);
        }

        // Find the most recent active trip for this driver
        // Assuming 'delivered' and 'cancelled' are terminal statuses
        $trip = Trip::with(['order', 'vehicle'])
            ->where('driver_id', $user->driver->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest()
            ->first();

        if (!$trip) {
            return response()->json(['message' => 'No active trip found.'], 404);
        }

        return response()->json(['trip' => $trip]);
    }

    /**
     * Update the status of a specific trip and log history.
     */
    public function updateStatus(Request $request, Trip $trip)
    {
        $user = $request->user();

        // Security check: Ensure the trip belongs to the authenticated driver
        if (!$user->driver || $trip->driver_id !== $user->driver->id) {
            return response()->json(['message' => 'Unauthorized access to this trip.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $trip->status;
        $newStatus = $validated['status'];

        if ($oldStatus === $newStatus && empty($validated['notes'])) {
             return response()->json(['message' => 'Status is already set to ' . $newStatus], 422);
        }

        // 1. Update the Trip Status
        $trip->update(['status' => $newStatus]);

        // 2. Create the History Log
        TripHistory::create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'action'  => 'status_update',
            'changes' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes'      => $validated['notes'] ?? null,
            ]
        ]);

        return response()->json([
            'message' => 'Trip status updated successfully.',
            'status' => $newStatus
        ]);
    }
}
