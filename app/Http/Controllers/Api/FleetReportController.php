<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Trip;
use App\Models\TrafficFine;
use App\Models\Trailer;
use App\Models\Requisition;
use App\Models\ExpenseType;
use App\Models\VehicleSnapshot;
use App\Models\TelemetryEvent;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FleetReportController extends Controller
{
    public function index(Request $request)
    {
        $today = now();

        // ── Currency handling ──
        $currencyService = app(CurrencyService::class);
        $currencies = Currency::all(['id', 'code', 'name', 'symbol', 'is_default']);
        $defaultCurrency = $currencies->firstWhere('is_default', true) ?? $currencies->first();
        $selectedCurrencyId = $request->integer('currency_id', $defaultCurrency?->id ?? 2);
        $selectedCurrency = $currencies->firstWhere('id', $selectedCurrencyId) ?? $defaultCurrency;
        $rwf = $currencies->firstWhere('code', 'RWF') ?? $defaultCurrency;
        $usd = $currencies->firstWhere('code', 'USD') ?? $defaultCurrency;
        $monthStart = $today->copy()->startOfMonth();
        $yearStart = $today->copy()->startOfYear();
        $twelveMonthsAgo = $today->copy()->subMonths(12);

        // ── Fleet Overview ──
        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();
        $inactiveVehicles = Vehicle::where('status', 'inactive')->count();

        $vehiclesWithoutDrivers = DB::table('vehicles')
            ->leftJoin('driver_vehicle_assignments', function ($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                    ->whereNull('driver_vehicle_assignments.end_date');
            })
            ->where('vehicles.status', 'active')
            ->whereNull('driver_vehicle_assignments.driver_id')
            ->count();

        // Currently moving
        $movingNow = VehicleSnapshot::where('is_moving', true)->count();

        $fleetOverview = [
            'total_vehicles' => $totalVehicles,
            'active' => $activeVehicles,
            'maintenance' => $maintenanceVehicles,
            'inactive' => $inactiveVehicles,
            'moving_now' => $movingNow,
            'idle' => $activeVehicles - $movingNow,
            'total_trailers' => Trailer::count(),
            'active_trailers' => Trailer::where('status', 'active')->count(),
            'utilization_rate' => $totalVehicles > 0 ? round(($activeVehicles / $totalVehicles) * 100, 1) : 0,
            'vehicles_without_drivers' => $vehiclesWithoutDrivers,
        ];

        // ── Business Metrics ──
        $totalOrders = Order::count();
        $deliveredOrders = Order::where('status', 'delivered')->count();
        $totalTrips = Trip::count();
        $completedTrips = Trip::where('status', 'delivered')->count();
        $activeTrips = Trip::whereIn('status', ['assigned', 'on_route'])->count();

        // Revenue from all orders (no status filter) — matches profit logic below
        $orderRevenueQuery = DB::table('orders');
        $totalRevenue = (float) (clone $orderRevenueQuery)->sum('price');
        $monthRevenue = (float) (clone $orderRevenueQuery)
            ->whereDate('orders.created_at', '>=', $monthStart)
            ->sum('price');

        // Top profitable clients
        $topClients = (clone $orderRevenueQuery)
            ->select('orders.client_id',
                DB::raw('COALESCE(users.name, clients.contact_person) as name'),
                DB::raw('SUM(orders.price) as total_revenue'),
                DB::raw('COUNT(*) as order_count'))
            ->leftJoin('clients', 'orders.client_id', '=', 'clients.id')
            ->leftJoin('users', 'clients.user_id', '=', 'users.id')
            ->groupBy('orders.client_id', 'users.name', 'clients.contact_person')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get()
            ->map(fn ($o) => [
                'name' => $o->name,
                'total_revenue' => (float) $o->total_revenue,
                'order_count' => (int) $o->order_count,
            ]);

        $businessMetrics = [
            'total_orders' => $totalOrders,
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'total_trips' => $totalTrips,
            'completed_trips' => $completedTrips,
            'active_trips' => $activeTrips,
            'pending_trips' => Trip::where('status', 'pending')->count(),
            'total_revenue' => $currencyService->convert($totalRevenue, $usd, $selectedCurrency),
            'month_revenue' => $currencyService->convert($monthRevenue, $usd, $selectedCurrency),
            'avg_revenue_per_trip' => $completedTrips > 0 ? round($currencyService->convert($totalRevenue, $usd, $selectedCurrency) / $completedTrips, 2) : 0,
            'delivery_rate' => $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 1) : 0,
            'top_clients' => $topClients->map(fn ($c) => array_merge($c, [
                'total_revenue' => $currencyService->convert($c['total_revenue'], $usd, $selectedCurrency),
            ])),
        ];

        // ── Compliance ──
        $todayDate = $today->copy()->startOfDay();

        $vehicles = Vehicle::with(['insurances', 'inspections', 'trafficFines'])->get()
            ->map(fn ($v) => $this->mapCompliance($v, $todayDate));

        $trailers = Trailer::with(['insurances', 'inspections', 'trafficFines'])->get()
            ->map(fn ($t) => $this->mapCompliance($t, $todayDate));

        $fullFleet = $vehicles->concat($trailers);
        $groundedCount = $fullFleet->where('is_grounded', true)->count();

        // Insurance expiring within 30 days
        $insuranceExpiring30d = DB::table('vehicle_insurances')
            ->where('expiry_date', '<=', $today->copy()->addDays(30))
            ->where('expiry_date', '>', $today)
            ->count();

        // Inspector overdue
        $inspectionsOverdue = DB::table('vehicle_inspections')
            ->where('completed_date', '<', $today)
            ->orWhere(function ($q) use ($today) {
                $q->whereNull('completed_date')
                    ->where('scheduled_date', '<', $today);
            })
            ->count();

        // Driver licenses expiring
        $licencesExpiring30d = Driver::whereDate('licence_expiry', '<=', $today->copy()->addDays(30))
            ->whereDate('licence_expiry', '>', $today)
            ->count();

        $compliance = [
            'health_percentage' => $fullFleet->count() > 0
                ? round(($fullFleet->where('is_grounded', false)->count() / $fullFleet->count()) * 100)
                : 100,
            'grounded' => $groundedCount,
            'insurance_expiring_30d' => $insuranceExpiring30d,
            'inspections_overdue' => $inspectionsOverdue,
            'fines_pending' => TrafficFine::where('status', 'PENDING')->count(),
            'licences_expiring_30d' => $licencesExpiring30d,
            'grounded_list' => $fullFleet->where('is_grounded', true)->take(5)->values(),
        ];

        // ── Financial: Cost vs Revenue ──
        $totalExpenses = (float) Requisition::whereIn('status', ['paid', 'approved'])->sum('amount');
        $totalFineCost = (float) TrafficFine::sum('ticket_amount');
        $totalFinePaid = (float) TrafficFine::where('status', 'PAID')->sum('paid_amount');
        $convertedRevenue = $currencyService->convert($totalRevenue, $usd, $selectedCurrency);
        $convertedExpenses = $currencyService->convert($totalExpenses, $usd, $selectedCurrency);
        $convertedFineCost = $currencyService->convert($totalFineCost, $rwf, $selectedCurrency);
        $convertedFinePaid = $currencyService->convert($totalFinePaid, $rwf, $selectedCurrency);
        $netProfit = $convertedRevenue - $convertedExpenses - $convertedFineCost;
        $profitMargin = $convertedRevenue > 0 ? round(($netProfit / $convertedRevenue) * 100, 1) : 0;

        // Monthly revenue trend — same order base as top clients
        $monthlyRevenue = (clone $orderRevenueQuery)
            ->where('orders.created_at', '>=', $twelveMonthsAgo)
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as month, SUM(orders.price) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('value', 'month');

        // Monthly expense trend (requisitions)
        $monthlyExpenses = Requisition::whereIn('status', ['paid', 'approved'])
            ->where('created_at', '>=', $twelveMonthsAgo)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('value', 'month');

        // Monthly fine costs
        $monthlyFines = TrafficFine::where('created_at', '>=', $twelveMonthsAgo)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(ticket_amount) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('value', 'month');

        // Build combined monthly labels & series (convert to selected currency)
        $monthlyLabels = [];
        $monthlyRevenueSeries = [];
        $monthlyExpensesSeries = [];
        $monthlyFinesSeries = [];
        for ($d = $twelveMonthsAgo->copy(); $d->lte($today); $d->addMonth()) {
            $key = $d->format('Y-m');
            $label = $d->format('M');
            $monthlyLabels[] = $label;
            $monthlyRevenueSeries[] = (float) $currencyService->convert($monthlyRevenue[$key] ?? 0, $usd, $selectedCurrency);
            $monthlyExpensesSeries[] = (float) $currencyService->convert($monthlyExpenses[$key] ?? 0, $usd, $selectedCurrency);
            $monthlyFinesSeries[] = (float) $currencyService->convert($monthlyFines[$key] ?? 0, $rwf, $selectedCurrency);
        }

        // Cost breakdown by expense type
        $costBreakdown = ExpenseType::where('is_active', true)
            ->leftJoin('requisitions', function ($join) use ($twelveMonthsAgo) {
                $join->on('expense_types.id', '=', 'requisitions.expense_type_id')
                    ->whereIn('requisitions.status', ['paid', 'approved'])
                    ->where('requisitions.created_at', '>=', $twelveMonthsAgo);
            })
            ->select('expense_types.name', DB::raw('COALESCE(SUM(requisitions.amount), 0) as amount'))
            ->groupBy('expense_types.id', 'expense_types.name')
            ->orderByDesc('amount')
            ->get();

        // Merge fines into total expenses (no special treatment)
        $convertedTotalExpenses = $convertedExpenses + $convertedFineCost;
        $mergedMonthlyExpenses = array_map(fn($e, $f) => $e + $f, $monthlyExpensesSeries, $monthlyFinesSeries);

        // Build cost breakdown including fines
        $costBreakdown = $costBreakdown->map(fn ($c) => [
            'name' => $c->name,
            'amount' => (float) $currencyService->convert((float) $c->amount, $usd, $selectedCurrency),
        ]);
        // Back-calculate total fines in selected currency for cost breakdown
        $totalFinesConverted = $currencyService->convert($totalFineCost, $rwf, $selectedCurrency);
        $costBreakdown->push(['name' => 'Fines', 'amount' => $totalFinesConverted]);
        $costBreakdown = $costBreakdown->sortByDesc('amount')->values();

        $financial = [
            'total_revenue' => $convertedRevenue,
            'total_expenses' => $convertedTotalExpenses,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
            'monthly_labels' => $monthlyLabels,
            'monthly_revenue' => $monthlyRevenueSeries,
            'monthly_expenses' => $mergedMonthlyExpenses,
            'cost_breakdown' => $costBreakdown,
        ];

        // ── Fuel Overview ──
        $dailyFuel = TelemetryEvent::whereDate('occurred_at', $today)
            ->selectRaw("SUM(CASE WHEN type = 'fuel_refill' THEN value ELSE 0 END) as total_refilled")
            ->selectRaw("SUM(CASE WHEN type = 'fuel_drain' THEN value ELSE 0 END) as total_stolen")
            ->first();

        $monthlyFuel = TelemetryEvent::where('occurred_at', '>=', $monthStart)
            ->selectRaw("SUM(CASE WHEN type = 'fuel_refill' THEN value ELSE 0 END) as total_refilled")
            ->selectRaw("SUM(CASE WHEN type = 'fuel_drain' THEN value ELSE 0 END) as total_stolen")
            ->first();

        $criticalFuelCount = VehicleSnapshot::where('low_fuel', true)
            ->where('fuel_level', '>', 0)
            ->count();

        $fuelConsumptionMonthly = TelemetryEvent::where('type', 'fuel_refill')
            ->where('occurred_at', '>=', $twelveMonthsAgo)
            ->selectRaw("DATE_FORMAT(occurred_at, '%Y-%m') as month, SUM(value) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('value', 'month');

        $fuelConsumptionSeries = [];
        for ($d = $twelveMonthsAgo->copy(); $d->lte($today); $d->addMonth()) {
            $key = $d->format('Y-m');
            $fuelConsumptionSeries[] = (float) ($fuelConsumptionMonthly[$key] ?? 0);
        }

        $fuel = [
            'today_filled' => (float) ($dailyFuel->total_refilled ?? 0),
            'today_stolen' => (float) ($dailyFuel->total_stolen ?? 0),
            'month_filled' => (float) ($monthlyFuel->total_refilled ?? 0),
            'month_stolen' => (float) ($monthlyFuel->total_stolen ?? 0),
            'critical_count' => $criticalFuelCount,
            'avg_fuel_level' => round(VehicleSnapshot::avg('fuel_level') ?? 0),
            'monthly_consumption' => $fuelConsumptionSeries,
        ];

        // ── Charts data ──
        $weekStart = $today->copy()->startOfWeek();

        $tripTimeline = Trip::whereBetween('created_at', [$weekStart, $today])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $orderStatusDist = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $fineTrends = TrafficFine::whereBetween('issued_at', [$weekStart, $today])
            ->selectRaw('DATE(issued_at) as date, COUNT(*) as count, SUM(ticket_amount) as total_amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($f) => [
                'date' => $f->date,
                'count' => (int) $f->count,
                'total_amount' => (float) $currencyService->convert((float) $f->total_amount, $rwf, $selectedCurrency),
            ]);

        $tripStatusDist = Trip::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return response()->json([
            'fleet' => $fleetOverview,
            'business' => $businessMetrics,
            'compliance' => $compliance,
            'financial' => $financial,
            'fuel' => $fuel,
            'charts' => [
                'trip_timeline' => $tripTimeline,
                'order_status_distribution' => $orderStatusDist,
                'fine_trends' => $fineTrends,
                'trip_status_distribution' => $tripStatusDist,
            ],
            'currencies' => $currencies->values(),
            'selected_currency' => [
                'id' => $selectedCurrency->id,
                'code' => $selectedCurrency->code,
                'symbol' => $selectedCurrency->symbol,
            ],
            'last_updated' => $today->toISOString(),
        ]);
    }

    private function mapCompliance($unit, $today)
    {
        $hasExpiredIns = $unit->insurances->where('expiry_date', '<', $today->toDateString())->isNotEmpty();
        $hasExpiredInsp = $unit->inspections->where('completed_date', '<', $today->toDateString())->isNotEmpty();
        $hasOverdueFine = $unit->trafficFines->where('status', 'PENDING')
            ->where('pay_by', '<', $today->toDateString())
            ->isNotEmpty();

        return [
            'plate' => $unit->plate_number,
            'type' => $unit instanceof \App\Models\Trailer ? 'Trailer' : 'Truck',
            'is_grounded' => ($hasExpiredIns || $hasExpiredInsp || $hasOverdueFine),
            'issues' => [
                'insurance' => $hasExpiredIns,
                'inspection' => $hasExpiredInsp,
                'fines' => $hasOverdueFine,
            ],
        ];
    }
}
