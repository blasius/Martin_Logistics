<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Order;
use Illuminate\Support\Collection;

class LoadOptimizationService
{
    public function findAvailableVehicles(array $criteria): Collection
    {
        return Vehicle::where('status', 'active')
            ->when($criteria['branch_id'] ?? null, fn($q, $id) => $q->where('branch_id', $id))
            ->when($criteria['vehicle_type_id'] ?? null, fn($q, $id) => $q->where('vehicle_type_id', $id))
            ->when($criteria['min_volume'] ?? null, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->whereNull('volume_capacity')->orWhere('volume_capacity', '>=', $v);
            }))
            ->when($criteria['min_payload'] ?? null, fn($q, $p) => $q->where(function ($q) use ($p) {
                $q->whereNull('max_payload')->orWhere('max_payload', '>=', $p);
            }))
            ->get();
    }

    public function consolidateLoads(Collection $orders, Collection $vehicles): array
    {
        $assignments = [];
        $remaining = collect();

        foreach ($orders as $order) {
            $assigned = false;
            $weight = $order->weight_kg ?? 0;
            $volume = $order->volume_m3 ?? 0;

            foreach ($vehicles as $vehicle) {
                $currentLoad = $assignments[$vehicle->id]['total_weight'] ?? 0;
                $currentVolume = $assignments[$vehicle->id]['total_volume'] ?? 0;

                if ($this->vehicleSuitability($vehicle, $weight + $currentLoad, $volume + $currentVolume)) {
                    $assignments[$vehicle->id]['vehicle'] = $vehicle;
                    $assignments[$vehicle->id]['orders'][] = $order;
                    $assignments[$vehicle->id]['total_weight'] = $currentLoad + $weight;
                    $assignments[$vehicle->id]['total_volume'] = $currentVolume + $volume;
                    $assigned = true;
                    break;
                }
            }

            if (!$assigned) {
                $remaining->push($order);
            }
        }

        return [
            'assignments' => array_values($assignments),
            'remaining' => $remaining,
        ];
    }

    public function vehicleSuitability(Vehicle $vehicle, float $totalWeight = 0, float $totalVolume = 0): bool
    {
        $weightOk = !$vehicle->max_payload || $totalWeight <= $vehicle->max_payload;
        $volumeOk = !$vehicle->volume_capacity || $totalVolume <= $vehicle->volume_capacity;
        return $weightOk && $volumeOk;
    }
}
