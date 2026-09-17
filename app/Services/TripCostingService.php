<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FuelDispense;
use App\Models\RepairRequestItem;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

/**
 * Trip-level P&L: reconciles revenue (order price) against the operational costs that
 * actually drive a trip — fuel dispenses, attributed maintenance (RepairRequest items)
 * and trip-linked expenses/allowances. Aggregate per period, per trip or as a dashboard KPI.
 *
 * Amounts are summed in their stored (default) currency, matching the semantics used by
 * the existing profitability report. Currency conversion remains the job of CurrencyService.
 */
class TripCostingService
{
    /**
     * Repair request statuses in which work has been performed and actual part/labour
     * costs have been recorded. Estimated figures are never treated as incurred cost.
     */
    protected array $costedRepairStatuses = ['completed', 'released'];

    /**
     * Full cost breakdown for one trip.
     */
    public function tripCostBreakdown(Trip $trip): array
    {
        $revenue = (float) ($trip->order?->price ?? 0);
        $fuelAmount = $this->fuelAmountForTrip($trip->id);
        $expenseAmount = $this->expenseAmountForTrip($trip->id);

        [$attribution, $unattributed] = $this->repairAttribution([$trip], $this->tripLowerBound($trip), now());
        $repairAmount = $attribution[$trip->id] ?? 0.0;

        $distance = $this->distanceKm($trip);
        $totalCost = $fuelAmount + $repairAmount + $expenseAmount;
        $profit = $revenue - $totalCost;

        return [
            'trip_id' => $trip->id,
            'reference' => $trip->reference,
            'vehicle' => $trip->vehicle_plate_snapshot ?: ($trip->vehicle_id ? ('Vehicle #' . $trip->vehicle_id) : 'Unknown'),
            'driver' => $trip->driver_name_snapshot ?: ($trip->driver_id ? ('Driver #' . $trip->driver_id) : 'Unknown'),
            'status' => $trip->status,
            'departed_at' => optional($trip->departure_time)->toIso8601String(),
            'distance_km' => $distance,
            'revenue' => round($revenue, 2),
            'fuel_liters' => $this->fuelLitersForTrip($trip->id),
            'fuel_amount' => round($fuelAmount, 2),
            'repair_amount' => round($repairAmount, 2),
            'expense_amount' => round($expenseAmount, 2),
            'total_cost' => round($totalCost, 2),
            'profit' => round($profit, 2),
            'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            'cost_per_km' => $distance > 0 ? round($totalCost / $distance, 2) : null,
            'unattributed_maintenance' => round($unattributed, 2),
        ];
    }

    /**
     * Period cost-vs-revenue report over delivered trips, grouped by a dimension.
     */
    public function periodCostReport(array $filters = []): array
    {
        $from = !empty($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : now()->startOfMonth();
        $to = !empty($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : now();
        $groupBy = $filters['group_by'] ?? 'truck';

        $trips = $this->deliveredTrips($from, $to, $filters);
        if ($trips->isEmpty()) {
            return $this->emptyReport($groupBy, $filters);
        }

        $tripIds = $trips->pluck('id');
        $fuelTotals = $this->fuelTotalsByTrip($tripIds);
        $expenseTotals = $this->expenseTotalsByTrip($tripIds);
        [$repairAttribution, $unattributed] = $this->repairAttribution($trips, $from, $to);

        $groups = [];
        $trend = [];
        foreach ($trips as $trip) {
            $revenue = (float) ($trip->order_price ?? 0);
            $fuelRow = $fuelTotals[$trip->id] ?? (object) ['liters' => 0, 'amount' => 0];
            $fuelAmount = (float) $fuelRow->amount;
            $fuelLiters = (float) $fuelRow->liters;
            $repairAmount = (float) ($repairAttribution[$trip->id] ?? 0);
            $expenseAmount = (float) ($expenseTotals[$trip->id] ?? 0);
            $distance = $this->distanceKm($trip);
            $totalCost = $fuelAmount + $repairAmount + $expenseAmount;
            $profit = $revenue - $totalCost;

            [$key, $label] = $this->groupKeyFor($trip, $groupBy);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $label, 'trips' => 0, 'distance_km' => 0,
                    'revenue' => 0, 'fuel_liters' => 0, 'fuel_amount' => 0,
                    'repair_amount' => 0, 'expense_amount' => 0, 'total_cost' => 0, 'profit' => 0,
                ];
            }
            $group = &$groups[$key];
            $group['trips']++;
            $group['distance_km'] += $distance;
            $group['revenue'] += $revenue;
            $group['fuel_liters'] += $fuelLiters;
            $group['fuel_amount'] += $fuelAmount;
            $group['repair_amount'] += $repairAmount;
            $group['expense_amount'] += $expenseAmount;
            $group['total_cost'] += $totalCost;
            $group['profit'] += $profit;
            unset($group);

            $monthKey = $trip->created_at?->format('Y-m') ?? 'n/a';
            if (!isset($trend[$monthKey])) {
                $trend[$monthKey] = [
                    'revenue' => 0, 'fuel_liters' => 0, 'fuel_amount' => 0,
                    'repair_amount' => 0, 'expense_amount' => 0, 'total_cost' => 0, 'profit' => 0,
                ];
            }
            $trend[$monthKey]['revenue'] += $revenue;
            $trend[$monthKey]['fuel_liters'] += $fuelLiters;
            $trend[$monthKey]['fuel_amount'] += $fuelAmount;
            $trend[$monthKey]['repair_amount'] += $repairAmount;
            $trend[$monthKey]['expense_amount'] += $expenseAmount;
            $trend[$monthKey]['total_cost'] += $totalCost;
            $trend[$monthKey]['profit'] += $profit;
        }

        $breakdown = collect($groups)
            ->map(function ($g) {
                $g['distance_km'] = round($g['distance_km'], 1);
                $g['trips'] = (int) $g['trips'];
                $g['revenue'] = round($g['revenue'], 2);
                $g['fuel_liters'] = round($g['fuel_liters'], 2);
                $g['fuel_amount'] = round($g['fuel_amount'], 2);
                $g['repair_amount'] = round($g['repair_amount'], 2);
                $g['expense_amount'] = round($g['expense_amount'], 2);
                $g['total_cost'] = round($g['total_cost'], 2);
                $g['profit'] = round($g['profit'], 2);
                $g['margin'] = $g['revenue'] > 0
                    ? round((($g['revenue'] - $g['total_cost']) / $g['revenue']) * 100, 1)
                    : 0;
                $g['cost_per_km'] = $g['distance_km'] > 0
                    ? round($g['total_cost'] / $g['distance_km'], 2)
                    : null;
                return $g;
            })
            ->sortByDesc('profit')
            ->values()
            ->all();

        $summaryKey = fn (string $col) => collect($breakdown)->sum($col);
        $totalRevenue = $summaryKey('revenue');
        $totalCost = $summaryKey('total_cost');
        $totalDistance = (float) $summaryKey('distance_km');
        $totalProfit = $totalRevenue - $totalCost;

        $trendSeries = [];
        for ($d = $from->copy()->startOfMonth(); $d->lte($to); $d->addMonth()) {
            $key = $d->format('Y-m');
            $t = $trend[$key] ?? [
                'revenue' => 0, 'fuel_liters' => 0, 'fuel_amount' => 0,
                'repair_amount' => 0, 'expense_amount' => 0, 'total_cost' => 0, 'profit' => 0,
            ];
            $trendSeries[] = [
                'period' => $d->format('M Y'),
                'revenue' => round($t['revenue'], 2),
                'fuel_liters' => round($t['fuel_liters'], 2),
                'fuel_amount' => round($t['fuel_amount'], 2),
                'repair_amount' => round($t['repair_amount'], 2),
                'expense_amount' => round($t['expense_amount'], 2),
                'total_cost' => round($t['total_cost'], 2),
                'profit' => round($t['profit'], 2),
                'margin' => $t['revenue'] > 0 ? round((($t['revenue'] - $t['total_cost']) / $t['revenue']) * 100, 1) : 0,
            ];
        }

        $profitableGroups = collect($breakdown)->where('profit', '>', 0)->values();
        $lossGroups = collect($breakdown)->where('profit', '<', 0)->values();

        return [
            'group_by' => $groupBy,
            'filters' => array_filter($filters, fn ($v) => $v !== '' && $v !== null),
            'summary' => [
                'total_trips' => (int) $trips->count(),
                'total_distance_km' => round($totalDistance, 1),
                'total_revenue' => round($totalRevenue, 2),
                'fuel_liters' => round($summaryKey('fuel_liters'), 2),
                'fuel_amount' => round($summaryKey('fuel_amount'), 2),
                'repair_amount' => round($summaryKey('repair_amount'), 2),
                'expense_amount' => round($summaryKey('expense_amount'), 2),
                'unattributed_maintenance' => round($unattributed, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($totalProfit, 2),
                'profit_margin' => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0,
                'avg_cost_per_trip' => $trips->isNotEmpty() ? round($totalCost / $trips->count(), 2) : 0,
                'avg_profit_per_trip' => $trips->isNotEmpty() ? round($totalProfit / $trips->count(), 2) : 0,
                'cost_per_km' => $totalDistance > 0 ? round($totalCost / $totalDistance, 2) : null,
                'profitable_groups' => $profitableGroups->count(),
                'loss_groups' => $lossGroups->count(),
            ],
            'breakdown' => $breakdown,
            'trend' => $trendSeries,
            'cost_breakdown' => [
                ['name' => 'Fuel', 'total' => round($summaryKey('fuel_amount'), 2)],
                ['name' => 'Maintenance', 'total' => round($summaryKey('repair_amount'), 2)],
                ['name' => 'Other / Allowances', 'total' => round($summaryKey('expense_amount'), 2)],
            ],
            'top_profitable' => $profitableGroups->take(5)->all(),
            'top_losses' => $lossGroups->take(5)->all(),
        ];
    }

    /**
     * Dashboard KPI: current-month cost-vs-revenue summary plus a trailing 12-month trend.
     */
    public function costVsRevenueKpi(Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now();

        $month = $this->periodCostReport(['from' => $from->toDateString(), 'to' => $to->toDateString()]);
        $trailing = $this->periodCostReport([
            'from' => now()->subMonths(11)->startOfMonth()->toDateString(),
            'to' => $to->toDateString(),
        ]);

        return [
            'month' => $month['summary'],
            'trend' => $trailing['trend'],
            'last_updated' => now()->toISOString(),
        ];
    }

    protected function deliveredTrips(Carbon $from, Carbon $to, array $filters): EloquentCollection
    {
        $query = Trip::query()
            ->leftJoin('orders', 'trips.order_id', '=', 'orders.id')
            ->leftJoin('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('drivers', 'trips.driver_id', '=', 'drivers.id')
            ->leftJoin('users as driver_users', 'drivers.user_id', '=', 'driver_users.id')
            ->leftJoin('users as dispatcher_users', 'trips.dispatcher_id', '=', 'dispatcher_users.id')
            ->leftJoin('routes', 'trips.route_id', '=', 'routes.id')
            ->leftJoin('clients', 'orders.client_id', '=', 'clients.id')
            ->leftJoin('users as client_users', 'clients.user_id', '=', 'client_users.id')
            ->where('trips.status', 'delivered')
            ->whereBetween('trips.created_at', [$from, $to])
            ->select([
                'trips.*',
                'orders.price as order_price',
                'orders.client_id as order_client_id',
                'vehicles.plate_number as vehicle_plate',
                'driver_users.name as driver_name',
                'dispatcher_users.name as dispatcher_name',
                'routes.name as route_name',
                'client_users.name as client_name',
                'clients.contact_person as client_contact',
            ]);

        if (!empty($filters['vehicle_id'])) {
            $query->where('trips.vehicle_id', $filters['vehicle_id']);
        }
        if (!empty($filters['driver_id'])) {
            $query->where('trips.driver_id', $filters['driver_id']);
        }
        if (!empty($filters['route_id'])) {
            $query->where('trips.route_id', $filters['route_id']);
        }
        if (!empty($filters['dispatcher_id'])) {
            $query->where('trips.dispatcher_id', $filters['dispatcher_id']);
        }
        if (!empty($filters['client_id'])) {
            $query->where('orders.client_id', $filters['client_id']);
        }

        return $query->get();
    }

    protected function fuelTotalsByTrip($tripIds)
    {
        if ($tripIds->isEmpty()) {
            return collect();
        }

        return FuelDispense::whereIn('trip_id', $tripIds)
            ->selectRaw('trip_id, SUM(quantity) as liters, SUM(COALESCE(calculated_amount, 0)) as amount')
            ->groupBy('trip_id')
            ->get()
            ->keyBy('trip_id');
    }

    protected function expenseTotalsByTrip($tripIds)
    {
        if ($tripIds->isEmpty()) {
            return collect();
        }

        return Expense::whereIn('trip_id', $tripIds)
            ->whereIn('status', ['approved', 'paid'])
            ->selectRaw('trip_id, SUM(amount) as total')
            ->groupBy('trip_id')
            ->pluck('total', 'trip_id');
    }

    /**
     * Attributing maintenance: a completed/released repair is charged to the trip of the same
     * vehicle that started last before the repair was submitted. Repairs whose owning trip is
     * outside the given set (or has no trip at all) are returned as unattributed maintenance so
     * maintenance spend on revenue-less periods is never silently dropped.
     *
     * @return array{0: array<int,float>, 1: float} [trip_id => repair cost, unattributed]
     */
    protected function repairAttribution(iterable $trips, Carbon $from, Carbon $to): array
    {
        $tripById = [];
        $vehicleIds = [];
        foreach ($trips as $trip) {
            $tripById[$trip->id] = true;
            if ($trip->vehicle_id) {
                $vehicleIds[$trip->vehicle_id] = true;
            }
        }
        if (empty($vehicleIds)) {
            return [[], 0.0];
        }

        $ownerSub = DB::table('trips as t2')
            ->selectRaw('t2.id')
            ->whereColumn('t2.vehicle_id', '=', DB::raw('repair_requests.vehicle_id'))
            ->whereColumn(
                DB::raw('COALESCE(t2.departure_time, t2.created_at)'),
                '<=',
                DB::raw('COALESCE(repair_requests.submitted_at, repair_requests.created_at)')
            )
            ->orderByDesc(DB::raw('COALESCE(t2.departure_time, t2.created_at)'))
            ->limit(1);

        $rows = RepairRequestItem::query()
            ->join('repair_requests', 'repair_requests.id', '=', 'repair_request_items.repair_request_id')
            ->whereIn('repair_requests.vehicle_id', array_keys($vehicleIds))
            ->whereIn('repair_requests.status', $this->costedRepairStatuses)
            ->whereBetween(DB::raw('COALESCE(repair_requests.submitted_at, repair_requests.created_at)'), [$from, $to])
            ->selectRaw('repair_request_items.repair_request_id, repair_requests.vehicle_id')
            ->selectRaw('SUM(COALESCE(repair_request_items.actual_total, 0)) as repair_total')
            ->addSelect([
                'owner_trip_id' => $ownerSub,
            ])
            ->groupBy('repair_request_items.repair_request_id', 'repair_requests.vehicle_id')
            ->get();

        $attribution = [];
        $unattributed = 0.0;
        foreach ($rows as $row) {
            if (isset($tripById[$row->owner_trip_id])) {
                $attribution[$row->owner_trip_id] = ($attribution[$row->owner_trip_id] ?? 0.0) + (float) $row->repair_total;
            } else {
                $unattributed += (float) $row->repair_total;
            }
        }

        return [$attribution, $unattributed];
    }

    protected function fuelAmountForTrip(int $tripId): float
    {
        return (float) FuelDispense::where('trip_id', $tripId)->sum('calculated_amount');
    }

    protected function fuelLitersForTrip(int $tripId): float
    {
        return (float) FuelDispense::where('trip_id', $tripId)->sum('quantity');
    }

    protected function expenseAmountForTrip(int $tripId): float
    {
        return (float) Expense::where('trip_id', $tripId)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');
    }

    protected function distanceKm(Trip $trip): float
    {
        $distance = $trip->actual_distance_km
            ? (float) $trip->actual_distance_km
            : (float) ($trip->planned_distance_km ?? 0);

        return round($distance, 1);
    }

    protected function tripLowerBound(Trip $trip): Carbon
    {
        return $trip->departure_time
            ? Carbon::parse($trip->departure_time)->subDay()
            : Carbon::parse($trip->created_at ?? now())->subDays(30);
    }

    protected function groupKeyFor(Trip $trip, string $groupBy): array
    {
        switch ($groupBy) {
            case 'trip':
                return [(string) $trip->id, $trip->reference ?: ('Trip #' . $trip->id)];
            case 'driver':
                return [
                    (string) ($trip->driver_id ?? 'n/a'),
                    $trip->driver_name ?: ($trip->driver_name_snapshot ?: 'Unknown'),
                ];
            case 'dispatcher':
                return [
                    (string) ($trip->dispatcher_id ?? 'n/a'),
                    $trip->dispatcher_name ?: 'Unknown',
                ];
            case 'route':
                return [
                    (string) ($trip->route_id ?? 'n/a'),
                    $trip->route_name ?: 'Unknown',
                ];
            case 'client':
                return [
                    (string) ($trip->order_client_id ?? 'n/a'),
                    $trip->client_name ?: ($trip->client_contact ?: 'Unknown'),
                ];
            case 'month':
                $key = $trip->created_at?->format('Y-m') ?? 'n/a';
                return [$key, $trip->created_at?->format('M Y') ?? 'Unknown'];
            case 'truck':
            default:
                return [
                    (string) ($trip->vehicle_id ?? 'n/a'),
                    $trip->vehicle_plate ?: ($trip->vehicle_plate_snapshot ?: 'Unknown'),
                ];
        }
    }

    protected function emptyReport(string $groupBy, array $filters): array
    {
        return [
            'group_by' => $groupBy,
            'filters' => array_filter($filters, fn ($v) => $v !== '' && $v !== null),
            'summary' => [
                'total_trips' => 0,
                'total_distance_km' => 0.0,
                'total_revenue' => 0.0,
                'fuel_liters' => 0.0,
                'fuel_amount' => 0.0,
                'repair_amount' => 0.0,
                'expense_amount' => 0.0,
                'unattributed_maintenance' => 0.0,
                'total_cost' => 0.0,
                'total_profit' => 0.0,
                'profit_margin' => 0,
                'avg_cost_per_trip' => 0,
                'avg_profit_per_trip' => 0,
                'cost_per_km' => null,
                'profitable_groups' => 0,
                'loss_groups' => 0,
            ],
            'breakdown' => [],
            'trend' => [],
            'cost_breakdown' => [
                ['name' => 'Fuel', 'total' => 0.0],
                ['name' => 'Maintenance', 'total' => 0.0],
                ['name' => 'Other / Allowances', 'total' => 0.0],
            ],
            'top_profitable' => [],
            'top_losses' => [],
        ];
    }
}