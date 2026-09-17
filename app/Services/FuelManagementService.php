<?php

namespace App\Services;

use App\Models\FuelDelivery;
use App\Models\FuelDispense;
use App\Models\FuelTank;
use App\Models\Route;
use App\Models\Trip;
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

    /**
     * Pre-departure fuel gate surfaced on the dispatch screen: expected
     * consumption (effective ratio x route distance) for the vehicle's next
     * ready-to-depart trip vs the current tank level from live telemetry.
     *
     * The "ready to depart" status is configurable in Portal → Trips → Trip Flow
     * (AppSetting trip_flow.ready_to_depart_state).
     */
    public function preDepartureFuelGate(Vehicle $vehicle, ?Trip $trip = null): array
    {
        $readyState = app(\App\Services\TripStateMachineService::class)
            ->configuredStateKey('trip_flow.ready_to_depart_state', 'assigned');

        $trip = $trip ?? Trip::where('vehicle_id', $vehicle->id)
            ->where('status', $readyState)
            ->latest()
            ->first();

        if (!$trip || !$trip->route_id) {
            return [
                'has_trip' => false,
                'trip_reference' => null,
                'status' => 'no_trip',
                'message' => 'No ready-to-depart trip for this unit.',
                'current_level' => round($this->getVehicleFuelLevel($vehicle), 2),
            ];
        }

        $ratio = $this->getEffectiveRatio($vehicle->id, $trip->route_id);
        $distance = (float) ($trip->route?->estimated_distance_km ?? 0);

        if (!$ratio || $distance <= 0) {
            return [
                'has_trip' => true,
                'trip_id' => $trip->id,
                'trip_reference' => $trip->reference,
                'status' => 'no_ratio',
                'message' => 'No fuel ratio configured for this vehicle-route pair.',
                'current_level' => round($this->getVehicleFuelLevel($vehicle), 2),
            ];
        }

        $expected = $distance / $ratio;
        $current = $this->getVehicleFuelLevel($vehicle);
        $requiredWithReserve = $expected + self::MIN_RESERVE_LITERS;

        if ($current < $expected) {
            $status = 'insufficient';
            $message = "Insufficient fuel: {$current}L in tank vs {$expected}L required for trip {$trip->reference}.";
        } elseif ($current < $requiredWithReserve) {
            $status = 'caution';
            $message = "Caution: {$current}L leaves less than the " . self::MIN_RESERVE_LITERS . "L reserve for trip {$trip->reference}.";
        } else {
            $status = 'ok';
            $message = 'Fuel adequate for the upcoming trip.';
        }

        return [
            'has_trip' => true,
            'trip_id' => $trip->id,
            'trip_reference' => $trip->reference,
            'route_name' => $trip->route?->name,
            'ratio_km_per_liter' => round($ratio, 2),
            'distance_km' => round($distance, 2),
            'expected_consumption' => round($expected, 2),
            'current_level' => round($current, 2),
            'reserve' => self::MIN_RESERVE_LITERS,
            'required_with_reserve' => round($requiredWithReserve, 2),
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Full per-trip fuel report: dispense entries, expected consumption from the
     * effective vehicle-route ratio, and variance. Reusable by the reports module.
     */
    public function tripFuelReport(int $tripId): array
    {
        $trip = Trip::with(['vehicle', 'route', 'driver.user'])->findOrFail($tripId);

        $dispenses = FuelDispense::with(['tank', 'driver.user'])
            ->where('trip_id', $tripId)
            ->orderBy('dispensed_at')
            ->get();

        $fuelUsed = (float) $dispenses->sum('quantity');
        $distance = (float) ($trip->route?->estimated_distance_km ?? $trip->actual_distance_km ?? 0);
        $ratio = $trip->route_id ? $this->getEffectiveRatio($trip->vehicle_id, $trip->route_id) : null;
        $expected = ($ratio && $distance > 0) ? $distance / $ratio : null;
        $variance = $expected !== null ? $fuelUsed - $expected : null;
        $variancePercent = $expected > 0 ? ($variance / $expected) * 100 : null;

        $analysis = TripFuelAnalysis::where('trip_id', $tripId)->first();

        return [
            'trip' => [
                'id' => $trip->id,
                'reference' => $trip->reference,
                'status' => $trip->status,
                'plate_number' => $trip->vehicle?->plate_number,
                'driver_name' => $trip->driver?->user?->name,
                'route_name' => $trip->route?->name,
                'created_at' => $trip->created_at?->toISOString(),
            ],
            'total_fuel_used' => round($fuelUsed, 2),
            'distance_km' => round($distance, 2),
            'ratio_km_per_liter' => $ratio ? round($ratio, 2) : null,
            'expected_consumption' => $expected !== null ? round($expected, 2) : null,
            'variance_liters' => $variance !== null ? round($variance, 2) : null,
            'variance_percent' => $variancePercent !== null ? round($variancePercent, 2) : null,
            'flag' => $analysis->flag ?? null,
            'analysed_at' => $analysis?->analysed_at?->toISOString(),
            'dispenses' => $dispenses->map(fn ($d) => [
                'id' => $d->id,
                'quantity' => (float) $d->quantity,
                'odometer_at_dispense' => (float) ($d->odometer_at_dispense ?? 0),
                'dispensed_at' => $d->dispensed_at?->toISOString(),
                'tank' => $d->tank?->name ?? ($d->tank?->code ?? '—'),
                'tank_fuel_type' => $d->tank?->fuel_type,
                'calculated_amount' => $d->calculated_amount !== null ? (float) $d->calculated_amount : null,
                'override_reason' => $d->override_reason,
                'notes' => $d->notes,
                'dispensed_by' => $d->dispenser?->name,
            ]),
        ];
    }

    /**
     * Filtered, paginated per-trip fuel report list with page totals.
     */
    public function tripFuelReports(array $filters = []): array
    {
        $query = Trip::query()
            ->with(['vehicle:id,plate_number,make,model', 'route:id,name,estimated_distance_km', 'driver.user:id,name'])
            ->whereNotNull('vehicle_id')
            ->whereNotNull('route_id');

        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }
        if (!empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['flag'])) {
            $query->whereHas('fuelAnalysis', fn ($q) => $q->where('flag', $filters['flag']));
        }

        $rows = (clone $query)
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 20);

        $tripIds = $rows->getCollection()->pluck('id');
        $fuelByTrip = FuelDispense::whereIn('trip_id', $tripIds)
            ->selectRaw('trip_id, SUM(quantity) as total')
            ->groupBy('trip_id')
            ->pluck('total', 'trip_id');

        $data = $rows->getCollection()->map(function (Trip $trip) use ($fuelByTrip) {
            $fuelUsed = (float) ($fuelByTrip[$trip->id] ?? 0);
            $distance = (float) ($trip->route?->estimated_distance_km ?? $trip->actual_distance_km ?? 0);
            $ratio = $trip->route_id ? $this->getEffectiveRatio($trip->vehicle_id, $trip->route_id) : null;
            $expected = ($ratio && $distance > 0) ? $distance / $ratio : null;
            $variance = $expected !== null ? $fuelUsed - $expected : null;
            $variancePercent = $expected > 0 ? ($variance / $expected) * 100 : null;
            $analysis = $trip->fuelAnalysis()->first(['id', 'flag']);

            return [
                'trip_id' => $trip->id,
                'reference' => $trip->reference,
                'status' => $trip->status,
                'plate_number' => $trip->vehicle?->plate_number,
                'driver_name' => $trip->driver?->user?->name,
                'route_name' => $trip->route?->name,
                'created_at' => $trip->created_at?->toISOString(),
                'distance_km' => round($distance, 2),
                'fuel_used' => round($fuelUsed, 2),
                'expected_consumption' => $expected !== null ? round($expected, 2) : null,
                'variance_liters' => $variance !== null ? round($variance, 2) : null,
                'variance_percent' => $variancePercent !== null ? round($variancePercent, 2) : null,
                'flag' => $analysis?->flag ?? null,
            ];
        });

        $totals = $this->fuelReportTotals(clone $query);

        return [
            'data' => $data,
            'totals' => $totals,
            'pagination' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
        ];
    }

    private function fuelReportTotals($query): array
    {
        $totals = [
            'trips' => 0,
            'fuel_used' => 0.0,
            'expected_consumption' => 0.0,
            'variance_liters' => 0.0,
        ];

        $count = 0;
        $variancePercentSum = 0.0;

        (clone $query)->with('route:id,estimated_distance_km')->select(['id', 'vehicle_id', 'route_id'])
            ->chunkById(500, function ($chunk) use (&$totals, &$count, &$variancePercentSum) {
                $ids = $chunk->pluck('id');
                $used = FuelDispense::whereIn('trip_id', $ids)
                    ->selectRaw('trip_id, SUM(quantity) as total')
                    ->groupBy('trip_id')
                    ->pluck('total', 'trip_id');

                foreach ($chunk as $trip) {
                    $fuelUsed = (float) ($used[$trip->id] ?? 0);
                    $distance = (float) ($trip->route?->estimated_distance_km ?? $trip->actual_distance_km ?? 0);
                    $ratio = $trip->route_id ? $this->getEffectiveRatio($trip->vehicle_id, $trip->route_id) : null;
                    $expected = ($ratio && $distance > 0) ? $distance / $ratio : 0;

                    $totals['trips']++;
                    $totals['fuel_used'] += $fuelUsed;
                    $totals['expected_consumption'] += $expected;
                    $totals['variance_liters'] += $fuelUsed - $expected;

                    if ($expected > 0) {
                        $count++;
                        $variancePercentSum += (($fuelUsed - $expected) / $expected) * 100;
                    }
                }
            });

        return [
            'trips' => $totals['trips'],
            'fuel_used' => round($totals['fuel_used'], 2),
            'expected_consumption' => round($totals['expected_consumption'], 2),
            'variance_liters' => round($totals['variance_liters'], 2),
            'avg_variance_percent' => $count > 0 ? round($variancePercentSum / $count, 2) : null,
        ];
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
