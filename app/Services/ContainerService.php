<?php

namespace App\Services;

use App\Models\Container;
use App\Models\ContainerMovement;
use App\Models\ContainerPenalty;
use App\Models\Place;
use App\Models\ShippingLineContract;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class ContainerService
{
    public function calculatePenalties(Container $container): array
    {
        $contract = $container->contract;
        if (!$contract) return [];

        $results = [];

        if ($container->current_status === 'at_port') {
            $penalty = $this->calculateDemurrage($container, $contract);
            if ($penalty) $results[] = $penalty;
        }

        if (in_array($container->current_status, ['at_warehouse', 'at_customer'])) {
            $penalty = $this->calculateDetention($container, $contract);
            if ($penalty) $results[] = $penalty;
        }

        return $results;
    }

    public function calculateDemurrage(Container $container, ShippingLineContract $contract): ?ContainerPenalty
    {
        $lastMovement = $container->movements()
            ->where('movement_type', 'port_pickup')
            ->latest('arrived_at')
            ->first();

        if (!$lastMovement || !$lastMovement->arrived_at) return null;

        $daysAtPort = $lastMovement->arrived_at->diffInDays(now());
        $freeDays = $contract->free_demurrage_days;

        if ($daysAtPort <= $freeDays) return null;

        $overdueDays = $daysAtPort - $freeDays;
        $rate = $this->findTierRate($contract->demurrage_tiers ?? [], $overdueDays, 0);

        $total = $overdueDays * $rate;

        return $this->savePenalty($container, 'demurrage', $overdueDays, $rate, $total);
    }

    public function calculateDetention(Container $container, ShippingLineContract $contract): ?ContainerPenalty
    {
        $lastMovement = $container->movements()
            ->whereIn('movement_type', ['delivery_to_customer', 'reposition', 'transfer'])
            ->latest('arrived_at')
            ->first();

        if (!$lastMovement || !$lastMovement->arrived_at) return null;

        $currentLocation = $container->currentLocation;
        if (!$currentLocation) return null;

        $arrivalDate = $lastMovement->arrived_at;
        $daysHere = $arrivalDate->diffInDays(now());
        $freeDays = $contract->free_detention_days;

        if ($daysHere <= $freeDays) return null;

        $overdueDays = $daysHere - $freeDays;
        $rate = $this->findTierRate($contract->detention_tiers ?? [], $overdueDays, 0);

        $total = $overdueDays * $rate;

        return $this->savePenalty($container, 'detention', $overdueDays, $rate, $total);
    }

    private function findTierRate(array $tiers, int $overdueDays, float $defaultRate): float
    {
        if (empty($tiers)) return $defaultRate;

        foreach ($tiers as $tier) {
            $from = $tier['days_from'] ?? 0;
            $to = $tier['days_to'] ?? PHP_INT_MAX;
            if ($overdueDays >= $from && $overdueDays <= $to) {
                return (float) ($tier['daily_rate'] ?? $defaultRate);
            }
        }

        $last = end($tiers);
        return (float) ($last['daily_rate'] ?? $defaultRate);
    }

    private function savePenalty(Container $container, string $type, int $days, float $rate, float $total): ContainerPenalty
    {
        $penalty = ContainerPenalty::updateOrCreate(
            [
                'container_id' => $container->id,
                'penalty_type' => $type,
            ],
            [
                'days_overdue' => $days,
                'daily_rate' => $rate,
                'total_amount' => $total,
                'calculated_at' => now(),
            ]
        );

        return $penalty;
    }

    public function batchCalculateAllPenalties(): int
    {
        $containers = Container::whereIn('current_status', ['at_port', 'at_warehouse', 'at_customer'])
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($containers as $container) {
            $this->calculatePenalties($container);
            $count++;
        }

        return $count;
    }

    public function recordMovement(array $data): ContainerMovement
    {
        $movement = ContainerMovement::create($data);

        $container = Container::find($data['container_id']);
        if ($container && isset($data['to_location_type'], $data['to_location_id'])) {
            $container->update([
                'current_location_type' => $data['to_location_type'],
                'current_location_id' => $data['to_location_id'],
            ]);
        }

        if ($container && isset($data['movement_type'])) {
            $statusMap = [
                'port_pickup' => 'in_transit',
                'delivery_to_customer' => 'at_customer',
                'return_to_depot' => 'empty_returned',
                'reposition' => 'at_warehouse',
                'transfer' => 'in_transit',
            ];
            $newStatus = $statusMap[$data['movement_type']] ?? $container->current_status;
            $container->update(['current_status' => $newStatus]);
        }

        return $movement;
    }

    public function dashboardStats(): array
    {
        $total = Container::where('is_active', true)->count();
        $byStatus = Container::where('is_active', true)
            ->select('current_status', DB::raw('count(*) as count'))
            ->groupBy('current_status')
            ->pluck('count', 'current_status')
            ->toArray();

        $overdue = Container::whereHas('penalties', function ($q) {
            $q->where('days_overdue', '>', 0);
        })->count();

        $totalPenalties = ContainerPenalty::sum('total_amount');

        $recentMovements = ContainerMovement::with('container')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'container_id' => $m->container?->container_id,
                'movement_type' => $m->movement_type,
                'arrived_at' => $m->arrived_at,
            ]);

        return [
            'total_containers' => $total,
            'by_status' => $byStatus,
            'overdue_containers' => $overdue,
            'total_penalties_amount' => $totalPenalties,
            'recent_movements' => $recentMovements,
        ];
    }

    public function overdueContainers(): array
    {
        return Container::whereHas('penalties', function ($q) {
            $q->where('days_overdue', '>', 0);
        })
            ->with(['contract', 'penalties'])
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'container_id' => $c->container_id,
                'size' => $c->size,
                'type' => $c->type,
                'owner_type' => $c->owner_type,
                'current_status' => $c->current_status,
                'shipping_line' => $c->contract?->shipping_line,
                'penalties' => $c->penalties->map(fn($p) => [
                    'type' => $p->penalty_type,
                    'days_overdue' => $p->days_overdue,
                    'daily_rate' => $p->daily_rate,
                    'total_amount' => $p->total_amount,
                ]),
            ])
            ->toArray();
    }

    public function searchLocations(string $query): array
    {
        $places = Place::where('name', 'like', "%{$query}%")
            ->orWhere('city', 'like', "%{$query}%")
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'type' => Place::class,
                'label' => $p->name . ($p->city ? ", {$p->city}" : ''),
                'subtype' => $p->type ?? 'place',
            ]);

        $warehouses = Warehouse::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'type' => Warehouse::class,
                'label' => "{$w->name} ({$w->code})",
                'subtype' => 'warehouse',
            ]);

        return $places->concat($warehouses)->toArray();
    }
}
