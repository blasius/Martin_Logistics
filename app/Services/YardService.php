<?php

namespace App\Services;

use App\Models\Place;
use App\Models\ServiceQueue;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class YardService
{
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

    public function queue(int $serviceType, ?string $status = null)
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
