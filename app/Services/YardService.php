<?php

namespace App\Services;

use App\Models\Place;
use App\Models\Vehicle;
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
