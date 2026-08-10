<?php

namespace App\Services;

use App\Models\FuelDelivery;
use App\Models\FuelDispense;
use App\Models\FuelTank;
use App\Models\Route;
use App\Models\TripFuelAnalysis;
use App\Models\DriverFuelRating;
use App\Models\Vehicle;
use App\Models\VehicleRouteFuelRatio;
use Illuminate\Support\Facades\DB;

class FuelManagementService
{
    const MIN_RESERVE_LITERS = 50;

    public function calculateDispenseAmount(Vehicle $vehicle, Route $route, ?float $currentFuelLevel = null): array
    {
        $ratio = $this->getEffectiveRatio($vehicle->id, $route->id);
        $distance = (float) ($route->estimated_distance_km ?? 0);
        $currentLevel = $currentFuelLevel ?? $this->getVehicleFuelLevel($vehicle);

        if (!$ratio) {
            return [
                'can_calculate' => false,
                'reason' => 'No fuel ratio configured for this vehicle-route pair.',
                'suggested_amount' => null,
                'breakdown' => null,
            ];
        }

        $routeConsumption = $distance / $ratio;

        $suggestedAmount = max(0, $routeConsumption - ($currentLevel - self::MIN_RESERVE_LITERS));

        return [
            'can_calculate' => true,
            'reason' => 'ok',
            'suggested_amount' => round($suggestedAmount, 2),
            'breakdown' => [
                'distance_km' => $distance,
                'km_per_liter' => $ratio,
                'route_consumption' => round($routeConsumption, 2),
                'current_level' => $currentLevel,
                'min_reserve' => self::MIN_RESERVE_LITERS,
                'available_for_trip' => max(0, $currentLevel - self::MIN_RESERVE_LITERS),
            ],
        ];
    }

    public function getEffectiveRatio(int $vehicleId, int $routeId): ?float
    {
        $record = VehicleRouteFuelRatio::where('vehicle_id', $vehicleId)
            ->where('route_id', $routeId)
            ->where(function ($q) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->latest('effective_from')
            ->first();

        if ($record) {
            return (float) $record->km_per_liter;
        }

        $vehicle = Vehicle::find($vehicleId);
        if ($vehicle && $vehicle->fuel_consumption_rate) {
            return 100 / (float) $vehicle->fuel_consumption_rate;
        }

        return null;
    }

    public function getVehicleFuelLevel(Vehicle $vehicle): float
    {
        $snapshot = $vehicle->snapshot;
        return $snapshot ? (float) ($snapshot->fuel_level ?? 0) : 0;
    }

    public function dispense(array $data): FuelDispense
    {
        return DB::transaction(function () use ($data) {
            $dispense = FuelDispense::create($data);

            if ($dispense->tank_id) {
                $dispense->tank->decrement('current_level', $dispense->quantity);
            }

            return $dispense;
        });
    }

    public function analyseTrip(int $tripId): TripFuelAnalysis
    {
        $trip = \App\Models\Trip::with('vehicle', 'route')->findOrFail($tripId);

        $dispenses = FuelDispense::where('trip_id', $tripId)->get();
        $fuelUsed = $dispenses->sum('quantity');

        $distance = (float) ($trip->route?->estimated_distance_km ?? 0);
        $ratio = $trip->route
            ? ($this->getEffectiveRatio($trip->vehicle_id, $trip->route->id) ?: 0)
            : 0;

        $expectedConsumption = $ratio > 0 && $distance > 0 ? $distance / $ratio : 0;
        $varianceLiters = $fuelUsed - $expectedConsumption;
        $variancePercent = $expectedConsumption > 0
            ? round(($varianceLiters / $expectedConsumption) * 100, 2)
            : 0;

        $flag = 'normal';
        if (abs($variancePercent) > 15) {
            $flag = 'excessive';
        } elseif (abs($variancePercent) > 5) {
            $flag = 'caution';
        }

        return TripFuelAnalysis::updateOrCreate(
            ['trip_id' => $tripId],
            [
                'vehicle_id' => $trip->vehicle_id,
                'route_id' => $trip->route?->id,
                'distance_km' => $distance,
                'fuel_used' => $fuelUsed,
                'expected_consumption' => round($expectedConsumption, 2),
                'variance_liters' => round($varianceLiters, 2),
                'variance_percent' => $variancePercent,
                'flag' => $flag,
                'analysed_at' => now(),
            ]
        );
    }

    public function rateDriver(int $driverId, string $periodStart, string $periodEnd): DriverFuelRating
    {
        $analyses = TripFuelAnalysis::whereHas('trip', fn($q) => $q->where('driver_id', $driverId))
            ->whereBetween('created_at', [$periodStart, $periodEnd . ' 23:59:59'])
            ->get();

        $totalTrips = $analyses->count();
        $flaggedTrips = $analyses->whereIn('flag', ['caution', 'excessive'])->count();
        $avgVariance = $totalTrips > 0 ? $analyses->avg('variance_percent') : 0;

        $rating = 'good';
        if ($avgVariance > 15 || ($totalTrips > 0 && $flaggedTrips / $totalTrips > 0.3)) {
            $rating = 'poor';
        } elseif ($avgVariance > 5 || ($totalTrips > 0 && $flaggedTrips / $totalTrips > 0.1)) {
            $rating = 'average';
        }

        return DriverFuelRating::updateOrCreate(
            ['driver_id' => $driverId, 'period_start' => $periodStart, 'period_end' => $periodEnd],
            [
                'avg_variance_percent' => round($avgVariance, 2),
                'total_trips' => $totalTrips,
                'flagged_trips' => $flaggedTrips,
                'rating' => $rating,
            ]
        );
    }

    public function pumpToTankVariance(?int $tankId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = FuelTank::where('is_active', true);
        if ($tankId) {
            $query->where('id', $tankId);
        }

        $dateFrom = $dateFrom ?? now()->startOfMonth()->toDateString();
        $dateTo = $dateTo ?? now()->toDateString();
        $results = [];

        foreach ($query->get() as $tank) {
            $deliveredInPeriod = FuelDelivery::where('tank_id', $tank->id)
                ->whereBetween('delivered_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->sum('quantity');

            $dispensedInPeriod = FuelDispense::where('tank_id', $tank->id)
                ->whereBetween('dispensed_at', [$dateFrom, $dateTo . ' 23:59:59'])
                ->sum('quantity');

            $deliveredAfter = FuelDelivery::where('tank_id', $tank->id)
                ->where('delivered_at', '>', $dateTo . ' 23:59:59')
                ->sum('quantity');

            $dispensedAfter = FuelDispense::where('tank_id', $tank->id)
                ->where('dispensed_at', '>', $dateTo . ' 23:59:59')
                ->sum('quantity');

            $closingLevel = (float) $tank->current_level + $dispensedAfter - $deliveredAfter;
            $openingLevel = $closingLevel - $deliveredInPeriod + $dispensedInPeriod;

            $bookStock = $openingLevel + $deliveredInPeriod;
            $physicalStock = $closingLevel + $dispensedInPeriod;
            $variance = round($bookStock - $physicalStock, 2);
            $totalThroughput = $deliveredInPeriod + $dispensedInPeriod;

            $results[] = [
                'tank_id' => $tank->id,
                'tank_name' => $tank->name,
                'fuel_type' => $tank->fuel_type,
                'opening_level' => round($openingLevel, 2),
                'delivered' => round($deliveredInPeriod, 2),
                'dispensed' => round($dispensedInPeriod, 2),
                'closing_level' => round($closingLevel, 2),
                'book_stock' => round($bookStock, 2),
                'physical_stock' => round($physicalStock, 2),
                'variance' => $variance,
                'variance_percent' => $totalThroughput > 0
                    ? round(($variance / $totalThroughput) * 100, 2) : 0,
            ];
        }

        return $results;
    }

    public function dashboardStats(): array
    {
        $tanks = FuelTank::where('is_active', true)->get();

        return [
            'total_tanks' => $tanks->count(),
            'total_capacity' => $tanks->sum('capacity'),
            'total_current' => $tanks->sum('current_level'),
            'overall_percent' => $tanks->sum('capacity') > 0
                ? round(($tanks->sum('current_level') / $tanks->sum('capacity')) * 100, 1) : 0,
            'low_stock_tanks' => $tanks->filter(fn($t) => $t->isLow())->values(),
            'dispensed_today' => FuelDispense::whereDate('dispensed_at', today())->sum('quantity'),
            'dispensed_this_month' => FuelDispense::whereMonth('dispensed_at', now()->month)
                ->whereYear('dispensed_at', now()->year)->sum('quantity'),
            'tanks' => $tanks->map(fn($t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'fuel_type' => $t->fuel_type,
                'capacity' => $t->capacity,
                'current_level' => $t->current_level,
                'percent' => $t->capacity > 0 ? round(($t->current_level / $t->capacity) * 100, 1) : 0,
                'is_low' => $t->isLow(),
            ]),
        ];
    }
}
