<?php

namespace App\Services;

use App\Models\DockDoor;
use App\Models\Place;
use App\Models\ProofOfDelivery;
use App\Models\ServiceQueue;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\YardEntry;
use Illuminate\Support\Facades\DB;

class YardService
{
    // --- Existing geofence methods ---

    public function findClosestYard(float $latitude, float $longitude): ?Place
    {
        $yards = Place::where('type', 'yard')->get(['id', 'name', 'latitude', 'longitude', 'radius_meters']);

        $closest = null;
        $closestDistance = null;

        foreach ($yards as $yard) {
            $distance = $this->haversine(
                $latitude, $longitude,
                (float) $yard->latitude, (float) $yard->longitude
            );

            if ($distance <= (float) ($yard->radius_meters ?? 50)) {
                if ($closest === null || $distance < $closestDistance) {
                    $closest = $yard;
                    $closestDistance = $distance;
                }
            }
        }

        return $closest;
    }

    public function isVehicleInYard(Vehicle $vehicle): bool
    {
        $snapshot = $vehicle->snapshot;

        if (!$snapshot || $snapshot->latitude === null || $snapshot->longitude === null) {
            return false;
        }

        return $this->findClosestYard(
            (float) $snapshot->latitude,
            (float) $snapshot->longitude
        ) !== null;
    }

    public function isVehicleInYardByCoordinates(float $latitude, float $longitude): bool
    {
        return $this->findClosestYard($latitude, $longitude) !== null;
    }

    // --- Queue methods ---

    public function enqueue(int $vehicleId, string $serviceType, int $submittedBy, ?array $coordinates = null): ServiceQueue
    {
        $geofenceVerified = false;

        if ($coordinates && isset($coordinates['lat'], $coordinates['lng'])) {
            $geofenceVerified = $this->isVehicleInYardByCoordinates(
                (float) $coordinates['lat'],
                (float) $coordinates['lng']
            );
        }

        $maxPosition = ServiceQueue::where('service_type', $serviceType)
            ->where('status', 'queued')
            ->max('position');

        return ServiceQueue::create([
            'vehicle_id' => $vehicleId,
            'service_type' => $serviceType,
            'priority' => 'normal',
            'status' => 'queued',
            'entered_at' => now(),
            'submitted_by' => $submittedBy,
            'coordinates' => $coordinates,
            'geofence_verified' => $geofenceVerified,
            'position' => ($maxPosition ?? 0) + 1,
        ]);
    }

    public function queue(string $serviceType, ?string $status = null)
    {
        $query = ServiceQueue::with(['vehicle:id,plate_number,make,model', 'submitter:id,name'])
            ->where('service_type', $serviceType)
            ->orderBy('position')
            ->orderBy('entered_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function startService(int $queueId): ServiceQueue
    {
        $entry = ServiceQueue::findOrFail($queueId);
        abort_unless($entry->status === 'queued', 422, 'Entry is not in queued status.');

        $entry->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $entry->fresh();
    }

    public function completeService(int $queueId): ServiceQueue
    {
        $entry = ServiceQueue::findOrFail($queueId);
        abort_unless($entry->status === 'in_progress', 422, 'Entry is not in progress.');

        $entry->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $entry->fresh();
    }

    public function skip(int $queueId): ServiceQueue
    {
        $entry = ServiceQueue::findOrFail($queueId);
        $entry->update(['status' => 'skipped']);

        return $entry->fresh();
    }

    public function reorder(int $queueId, int $newPosition): void
    {
        DB::transaction(function () use ($queueId, $newPosition) {
            $entry = ServiceQueue::findOrFail($queueId);

            $currentPosition = $entry->position;
            $serviceType = $entry->service_type;

            if ($newPosition < $currentPosition) {
                ServiceQueue::where('service_type', $serviceType)
                    ->where('status', 'queued')
                    ->whereBetween('position', [$newPosition, $currentPosition - 1])
                    ->where('id', '!=', $queueId)
                    ->increment('position');
            } elseif ($newPosition > $currentPosition) {
                ServiceQueue::where('service_type', $serviceType)
                    ->where('status', 'queued')
                    ->whereBetween('position', [$currentPosition + 1, $newPosition])
                    ->where('id', '!=', $queueId)
                    ->decrement('position');
            }

            $entry->update(['position' => $newPosition]);
        });
    }

    // --- Station assignment ---

    public function assignStation(int $queueId, string $station): ServiceQueue
    {
        $entry = ServiceQueue::findOrFail($queueId);
        abort_unless($entry->status === 'queued', 422, 'Entry is not queued.');

        $entry->update(['assigned_station' => $station]);

        return $entry->fresh();
    }

    public function assignDockDoor(int $queueId, int $dockDoorId): ServiceQueue
    {
        $entry = ServiceQueue::findOrFail($queueId);
        $door = DockDoor::findOrFail($dockDoorId);

        abort_if($door->is_occupied, 422, 'Dock door is already occupied.');
        abort_unless($entry->status === 'queued', 422, 'Entry is not queued.');
        abort_unless($door->is_active, 422, 'Dock door is not active.');

        DB::transaction(function () use ($entry, $door) {
            $entry->update([
                'assigned_station' => $door->code,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $door->update([
                'is_occupied' => true,
                'current_vehicle_id' => $entry->vehicle_id,
                'current_queue_id' => $entry->id,
                'occupied_since' => now(),
            ]);
        });

        return $entry->fresh()->load('vehicle:id,plate_number,make,model');
    }

    public function releaseDockDoor(int $dockDoorId): void
    {
        $door = DockDoor::findOrFail($dockDoorId);

        DB::transaction(function () use ($door) {
            $queueId = $door->current_queue_id;

            if ($queueId) {
                $entry = ServiceQueue::find($queueId);
                if ($entry && $entry->status === 'in_progress') {
                    $entry->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                }
            }

            $door->update([
                'is_occupied' => false,
                'current_vehicle_id' => null,
                'current_queue_id' => null,
                'occupied_since' => null,
            ]);
        });
    }

    public function autoAssignNext(string $serviceType): ?ServiceQueue
    {
        $availableDoor = DockDoor::where('service_type', $serviceType)
            ->where('is_active', true)
            ->where('is_occupied', false)
            ->first();

        if (!$availableDoor) return null;

        $nextInQueue = ServiceQueue::where('service_type', $serviceType)
            ->where('status', 'queued')
            ->orderBy('position')
            ->orderBy('entered_at')
            ->first();

        if (!$nextInQueue) return null;

        return $this->assignDockDoor($nextInQueue->id, $availableDoor->id);
    }

    // --- Wait time estimation ---

    public function averageServiceTime(string $serviceType): float
    {
        $avg = ServiceQueue::where('service_type', $serviceType)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_minutes')
            ->value('avg_minutes');

        return (float) ($avg ?? 15);
    }

    public function estimatedWaitTime(string $serviceType): array
    {
        $ahead = ServiceQueue::where('service_type', $serviceType)
            ->where('status', 'queued')
            ->count();

        $avgTime = $this->averageServiceTime($serviceType);

        return [
            'vehicles_ahead' => $ahead,
            'average_service_minutes' => round($avgTime, 1),
            'estimated_wait_minutes' => round($ahead * $avgTime, 1),
            'service_type' => $serviceType,
        ];
    }

    // --- Yard check-in / check-out ---

    public function checkIn(int $vehicleId, string $purpose, ?int $driverId = null, ?int $checkedInBy = null, ?array $coordinates = null): YardEntry
    {
        $entry = YardEntry::create([
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'purpose' => $purpose,
            'check_in_at' => now(),
            'checked_in_by' => $checkedInBy ?? $driverId,
        ]);

        return $entry->load('vehicle:id,plate_number,make,model', 'driver:id');
    }

    public function checkOut(int $yardEntryId): YardEntry
    {
        $entry = YardEntry::findOrFail($yardEntryId);
        abort_unless($entry->check_out_at === null, 422, 'Already checked out.');

        DB::transaction(function () use ($entry) {
            $entry->update(['check_out_at' => now()]);

            // Free the dock door when the vehicle leaves the yard.
            if ($entry->service_queue_id) {
                $queue = ServiceQueue::find($entry->service_queue_id);
                if ($queue && $queue->status === 'in_progress') {
                    $door = DockDoor::where('current_queue_id', $queue->id)
                        ->where('current_vehicle_id', $entry->vehicle_id)
                        ->first();
                    if ($door) {
                        $this->releaseDockDoor($door->id);
                    }
                }
            }
        });

        return $entry->fresh();
    }

    public function mobileCheckIn(int $vehicleId, int $driverId, string $serviceType, ?array $coordinates = null): array
    {
        $yardEntry = $this->checkIn($vehicleId, $serviceType, $driverId, $driverId, $coordinates);

        $queueEntry = $this->enqueue($vehicleId, $serviceType, $driverId, $coordinates);

        $yardEntry->update(['service_queue_id' => $queueEntry->id]);

        return [
            'yard_entry' => $yardEntry->fresh(),
            'queue_entry' => $queueEntry->fresh()->load('vehicle:id,plate_number,make,model'),
        ];
    }

    // --- Arrival / dock automation ---

    /**
     * On POD validation, register the vehicle at the yard (unloading), enqueue
     * it on the unload dock and allocate a free dock door so the driver knows
     * where to park. Reuses an open unloading entry / active queue if present.
     */
    public function scheduleUnloadingAfterDelivery(Trip $trip, ProofOfDelivery $pod): array
    {
        $vehicleId = $trip->vehicle_id;
        if (!$vehicleId) {
            return ['yard_entry' => null, 'queue_entry' => null, 'dock_door' => null];
        }

        $submittedBy = $pod->submitted_by ?? $trip->created_by;
        $driverId = $trip->driver_id ?? $pod->submitter?->driver_id;

        return DB::transaction(function () use ($trip, $pod, $vehicleId, $submittedBy, $driverId) {
            $yardEntry = YardEntry::where('vehicle_id', $vehicleId)
                ->whereNull('check_out_at')
                ->where('purpose', 'unloading')
                ->latest('check_in_at')
                ->first();

            if (!$yardEntry) {
                $yardEntry = $this->checkIn($vehicleId, 'unloading', $driverId, $submittedBy);
            }

            $queueEntry = ServiceQueue::where('vehicle_id', $vehicleId)
                ->where('service_type', 'unload_dock')
                ->whereIn('status', ['queued', 'in_progress'])
                ->latest('entered_at')
                ->first();

            if (!$queueEntry) {
                $queueEntry = $this->enqueue($vehicleId, 'unload_dock', $submittedBy);
            }

            $yardEntry->update(['service_queue_id' => $queueEntry->id]);

            $dockDoor = null;
            if ($queueEntry->status === 'queued') {
                $dockDoor = DockDoor::where('service_type', 'unload_dock')
                    ->where('is_active', true)
                    ->where('is_occupied', false)
                    ->orderBy('code')
                    ->first();

                if ($dockDoor) {
                    $this->assignDockDoor($queueEntry->id, $dockDoor->id);
                    $yardEntry->update(['dock_door_id' => $dockDoor->id]);
                    $dockDoor->refresh();
                }
            }

            return [
                'yard_entry' => $yardEntry->fresh(),
                'queue_entry' => $queueEntry->fresh()->load('vehicle:id,plate_number,make,model'),
                'dock_door' => $dockDoor?->fresh(),
            ];
        });
    }

    /**
     * Dock doors that are currently occupied, plus queued/in-progress unload
     * arrivals still waiting for a dock — powers the dispatcher yard map.
     */
    public function expectedArrivals(): array
    {
        return ServiceQueue::with(['vehicle:id,plate_number,make,model', 'submitter:id,name'])
            ->where('service_type', 'unload_dock')
            ->whereIn('status', ['queued', 'in_progress'])
            ->orderBy('position')
            ->orderBy('entered_at')
            ->get()
            ->map(function ($entry) {
                $entry->wait_estimate = $this->estimatedWaitTime('unload_dock');
                return $entry;
            })
            ->values()
            ->toArray();
    }

    // --- Dashboard ---

    public function dashboardStats(): array
    {
        $serviceTypes = ['offload', 'wash', 'workshop', 'fuel', 'loading_dock', 'unload_dock', 'fueling_bay', 'car_wash'];

        $queues = [];
        foreach ($serviceTypes as $type) {
            $queues[$type] = [
                'queued' => ServiceQueue::where('service_type', $type)->where('status', 'queued')->count(),
                'in_progress' => ServiceQueue::where('service_type', $type)->where('status', 'in_progress')->count(),
                'wait_time' => $this->estimatedWaitTime($type),
            ];
        }

        $occupiedDoors = DockDoor::where('is_occupied', true)->count();
        $totalDoors = DockDoor::where('is_active', true)->count();

        $activeEntries = YardEntry::whereNull('check_out_at')->count();

        $recentEntries = YardEntry::with(['vehicle:id,plate_number,make,model', 'driver:id', 'dockDoor:id,code'])
            ->orderByDesc('check_in_at')
            ->limit(10)
            ->get();

        return [
            'queues' => $queues,
            'dock_doors' => [
                'occupied' => $occupiedDoors,
                'available' => $totalDoors - $occupiedDoors,
                'total' => $totalDoors,
            ],
            'active_entries' => $activeEntries,
            'recent_entries' => $recentEntries,
            'expected_arrivals' => $this->expectedArrivals(),
            'stats' => $this->queueStats(),
        ];
    }

    public function queueStats(): array
    {
        return [
            'total_queued' => ServiceQueue::where('status', 'queued')->count(),
            'in_progress' => ServiceQueue::where('status', 'in_progress')->count(),
            'completed_today' => ServiceQueue::where('status', 'completed')
                ->whereDate('completed_at', today())->count(),
            'by_type' => ServiceQueue::selectRaw("service_type, count(*) as count")
                ->where('status', 'queued')
                ->groupBy('service_type')
                ->pluck('count', 'service_type'),
        ];
    }

    // --- Private helpers ---

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
