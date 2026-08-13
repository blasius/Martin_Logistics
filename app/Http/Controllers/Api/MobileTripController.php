<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverVehicleAssignment;
use App\Models\Place;
use App\Models\Trip;
use App\Models\TripHistory;
use App\Models\User;
use App\Models\VehicleSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileTripController extends Controller
{
    /**
     * Get the driver's currently active trip and the operational context
     * (driver identity, assigned truck/trailer, position, nearest place,
     * assigned staff contact and fuel level) used by the mobile home screen.
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
        $trip = Trip::with(['order', 'vehicle', 'route', 'dispatcher', 'createdBy'])
            ->where('driver_id', $user->driver->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest()
            ->first();

        if (!$trip) {
            return response()->json(['message' => 'No active trip found.'], 404);
        }

        // Assigned truck comes from the trip when present, otherwise from the
        // driver's active vehicle assignment.
        $vehicle = $trip->vehicle
            ?: DriverVehicleAssignment::query()
                ->with('vehicle')
                ->where('driver_id', $user->id)
                ->whereNull('end_date')
                ->latest('start_date')
                ->first()?->vehicle;

        $snapshot = $vehicle
            ? VehicleSnapshot::where('vehicle_id', $vehicle->id)->first()
            : null;

        return response()->json([
            'message' => 'Active trip found.',
            'driver' => $this->driverPayload($user),
            'vehicle' => $vehicle ? $this->vehiclePayload($vehicle) : null,
            'position' => $snapshot ? $this->positionPayload($snapshot) : null,
            'nearest_place' => $snapshot ? $this->nearestPlace($snapshot) : null,
            'assigned_staff' => $this->staffPayload($trip->dispatcher ?? $trip->createdBy),
            'trip' => $trip,
        ]);
    }

    /**
     * Personal identity of the authenticated driver.
     */
    private function driverPayload(User $user): array
    {
        $driver = $user->driver;

        return [
            'id' => $driver->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $driver->phone,
            'whatsapp_phone' => $driver->whatsapp_phone,
            'nationality' => $driver->nationality,
            'branch' => $driver->branch?->name ?? $user->branch?->name,
        ];
    }

    /**
     * Assigned truck (and linked trailer) for the current trip.
     */
    private function vehiclePayload($vehicle): array
    {
        $trailer = $vehicle->currentAssignment?->trailer;

        return [
            'id' => $vehicle->id,
            'plate_number' => $vehicle->plate_number,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'fuel_type' => $vehicle->fuel_type,
            'tank_capacity' => $vehicle->tank_capacity,
            'status' => $vehicle->status,
            'trailer' => $trailer ? [
                'id' => $trailer->id,
                'plate_number' => $trailer->plate_number,
            ] : null,
        ];
    }

    /**
     * Latest telemetry position/fuel snapshot for the assigned truck.
     */
    private function positionPayload(VehicleSnapshot $snapshot): array
    {
        $stale = $snapshot->last_seen_at
            ? $snapshot->last_seen_at->lt(now()->subMinutes(10))
            : true;

        return [
            'latitude' => (float) $snapshot->latitude,
            'longitude' => (float) $snapshot->longitude,
            'speed' => (float) $snapshot->speed,
            'fuel_level' => (float) $snapshot->fuel_level,
            'last_seen_at' => $snapshot->last_seen_at,
            'is_moving' => (bool) $snapshot->is_moving,
            'ignition' => (bool) $snapshot->ignition,
            'is_stale' => $stale,
        ];
    }

    /**
     * Nearest registered place to the truck, mirroring the portal tracker.
     */
    private function nearestPlace(VehicleSnapshot $snapshot): ?Place
    {
        if (!$snapshot->latitude || !$snapshot->longitude) {
            return null;
        }

        $pointWkt = "POINT({$snapshot->longitude} {$snapshot->latitude})";

        return Place::select(
            'id', 'place_key', 'name', 'type', 'city', 'latitude', 'longitude', 'radius_meters',
            DB::raw("ROUND(ST_Distance_Sphere(location, ST_GeomFromText('{$pointWkt}', 4326)), 0) AS distance_meters")
        )
            ->orderBy('distance_meters')
            ->limit(1)
            ->first();
    }

    /**
     * Staff member assigned to the trip (dispatcher, falling back to creator)
     * with their best-known contact details.
     */
    private function staffPayload(?User $staff): ?array
    {
        if (!$staff) {
            return null;
        }

        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'roles' => $staff->getRoleNames()->all(),
            'phone' => $this->userContact($staff, 'phone'),
            'whatsapp' => $this->userContact($staff, 'whatsapp'),
        ];
    }

    /**
     * Primary contact of a given type for a user (verified preferred).
     */
    private function userContact(User $user, string $type): ?string
    {
        return $user->contacts()
            ->where('type', $type)
            ->orderByDesc('verified_at')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->value('value');
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
