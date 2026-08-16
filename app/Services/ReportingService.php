<?php

namespace App\Services;

use App\Models\Container;
use App\Models\ContainerPenalty;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PerformanceScore;
use App\Models\Trip;
use App\Models\Wallet;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Requisition;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    protected CurrencyService $currencyService;
    protected Currency $usd;
    protected Currency $rwf;

    public function __construct()
    {
        $this->currencyService = app(CurrencyService::class);
        $this->usd = Currency::where('code', 'USD')->first() ?? Currency::first();
        $this->rwf = Currency::where('code', 'RWF')->first() ?? $this->usd;
    }

    public function containerReport(): array
    {
        $total = Container::count();
        $active = Container::where('is_active', true)->count();
        $owned = Container::where('is_owned', true)->count();
        $leased = $total - $owned;

        $statusDist = Container::selectRaw('current_status, COUNT(*) as count')
            ->groupBy('current_status')
            ->pluck('count', 'current_status');

        $totalPenalties = (float) ContainerPenalty::sum('total_amount');
        $recentPenalties = (float) ContainerPenalty::where('created_at', '>=', now()->subMonth())->sum('total_amount');

        $sizeDist = Container::selectRaw('size, COUNT(*) as count')
            ->groupBy('size')
            ->pluck('count', 'size');

        $convertedPenalties = $this->currencyService->convert($totalPenalties, $this->rwf, $this->usd);

        return [
            'total' => $total,
            'active' => $active,
            'owned' => $owned,
            'leased' => $leased,
            'status_distribution' => $statusDist,
            'size_distribution' => $sizeDist,
            'total_penalties' => $convertedPenalties,
            'total_penalties_raw' => $totalPenalties,
            'recent_penalties' => $this->currencyService->convert($recentPenalties, $this->rwf, $this->usd),
            'recent_penalties_raw' => $recentPenalties,
        ];
    }

    public function expenseReport(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfYear();
        $endDate = $endDate ?? now();

        $totalExpenses = (float) Expense::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $pendingExpenses = (float) Expense::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'pending')
            ->sum('amount');

        $byCategory = Expense::whereBetween('expenses.created_at', [$startDate, $endDate])
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->join('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->select('expense_types.name', DB::raw('SUM(expenses.amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('expense_types.id', 'expense_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($e) => [
                'name' => $e->name,
                'total' => (float) $e->total,
                'count' => (int) $e->count,
            ]);

        $byVehicle = Expense::whereBetween('expenses.created_at', [$startDate, $endDate])
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->whereNotNull('expenses.vehicle_id')
            ->join('vehicles', 'expenses.vehicle_id', '=', 'vehicles.id')
            ->select('vehicles.plate_number', DB::raw('SUM(expenses.amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('vehicles.id', 'vehicles.plate_number')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($e) => [
                'plate' => $e->plate_number,
                'total' => (float) $e->total,
                'count' => (int) $e->count,
            ]);

        $monthly = Expense::whereBetween('expenses.created_at', [$startDate, $endDate])
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->selectRaw("DATE_FORMAT(expenses.created_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyLabels = [];
        $monthlySeries = [];
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addMonth()) {
            $key = $d->format('Y-m');
            $monthlyLabels[] = $d->format('M y');
            $monthlySeries[] = (float) ($monthly[$key] ?? 0);
        }

        return [
            'total' => $totalExpenses,
            'pending' => $pendingExpenses,
            'approved_count' => Expense::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['approved', 'paid'])->count(),
            'pending_count' => Expense::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'pending')->count(),
            'by_category' => $byCategory,
            'by_vehicle' => $byVehicle,
            'monthly_labels' => $monthlyLabels,
            'monthly_series' => $monthlySeries,
        ];
    }

    public function performanceReport(): array
    {
        $latestScores = PerformanceScore::where('period_end', '>=', now()->subMonth())
            ->get();

        $driverScores = $latestScores->where('scoreable_type', 'driver');
        $dispatcherScores = $latestScores->where('scoreable_type', 'user');

        return [
            'drivers_scored' => $driverScores->count(),
            'dispatchers_scored' => $dispatcherScores->count(),
            'avg_driver_score' => $driverScores->avg('overall_score') ?? 0,
            'avg_dispatcher_score' => $dispatcherScores->avg('overall_score') ?? 0,
            'avg_fuel_efficiency' => $driverScores->avg('fuel_efficiency_score') ?? 0,
            'avg_on_time_delivery' => $driverScores->avg('on_time_delivery_score') ?? 0,
            'avg_route_compliance' => $driverScores->avg('route_compliance_score') ?? 0,
            'total_human_ratings' => $latestScores->sum('human_rating_count') ?? 0,
        ];
    }

    public function walletReport(): array
    {
        $totalWallets = Wallet::count();
        $totalBalance = (float) Wallet::sum('current_balance');
        $walletsByCurrency = Wallet::selectRaw('currency_id, SUM(current_balance) as total, COUNT(*) as count')
            ->groupBy('currency_id')
            ->with('currency:id,code,symbol')
            ->get()
            ->map(fn ($w) => [
                'currency' => $w->currency?->code ?? 'Unknown',
                'symbol' => $w->currency?->symbol ?? '',
                'total' => (float) $w->total,
                'count' => (int) $w->count,
            ]);

        $recentTransactions = DB::table('wallet_transactions')
            ->where('created_at', '>=', now()->subMonth())
            ->selectRaw("
                SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_credits,
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_debits,
                COUNT(*) as transaction_count
            ")
            ->first();

        return [
            'total_wallets' => $totalWallets,
            'total_balance' => $totalBalance,
            'by_currency' => $walletsByCurrency,
            'month_credits' => (float) ($recentTransactions->total_credits ?? 0),
            'month_debits' => (float) ($recentTransactions->total_debits ?? 0),
            'month_transactions' => (int) ($recentTransactions->transaction_count ?? 0),
        ];
    }

    public function invoiceReport(): array
    {
        $totalInvoices = Invoice::count();
        $totalOutstanding = (float) Invoice::whereIn('status', ['sent', 'overdue', 'draft'])->sum('total');
        $totalPaid = (float) Invoice::where('status', 'paid')->sum('total');
        $overdueInvoices = (float) Invoice::where('status', 'overdue')->sum('total');

        $statusDist = Invoice::selectRaw('status, COUNT(*) as count, SUM(total) as total_amount')
            ->groupBy('status')
            ->get()
            ->map(fn ($i) => [
                'status' => $i->status,
                'count' => (int) $i->count,
                'amount' => (float) $i->total_amount,
            ]);

        $monthlyInvoiced = Invoice::where('created_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyLabels = [];
        $monthlySeries = [];
        for ($d = now()->subMonths(12)->copy(); $d->lte(now()); $d->addMonth()) {
            $key = $d->format('Y-m');
            $monthlyLabels[] = $d->format('M y');
            $monthlySeries[] = (float) ($monthlyInvoiced[$key] ?? 0);
        }

        $collectionRate = $totalInvoices > 0
            ? round(($totalPaid / ($totalPaid + $totalOutstanding)) * 100, 1)
            : 0;

        return [
            'total_invoices' => $totalInvoices,
            'total_outstanding' => $totalOutstanding,
            'total_paid' => $totalPaid,
            'overdue' => $overdueInvoices,
            'collection_rate' => $collectionRate,
            'status_distribution' => $statusDist,
            'monthly_labels' => $monthlyLabels,
            'monthly_series' => $monthlySeries,
        ];
    }

    public function tripProfitabilityReport(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now();

        $trips = Trip::whereBetween('trips.created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->with('order')
            ->get();

        $totalTrips = $trips->count();
        $tripRevenue = 0;
        $tripExpenses = 0;

        $tripIds = $trips->pluck('id');

        // Revenue from orders
        $orderIds = $trips->pluck('order_id')->filter();
        if ($orderIds->isNotEmpty()) {
            $tripRevenue = (float) Order::whereIn('id', $orderIds)->sum('price');
        }

        // Expenses from expenses table linked to trips
        if ($tripIds->isNotEmpty()) {
            $tripExpenses = (float) Expense::whereIn('trip_id', $tripIds)
                ->whereIn('status', ['approved', 'paid'])
                ->sum('amount');
        }

        $profit = $tripRevenue - $tripExpenses;
        $margin = $tripRevenue > 0 ? round(($profit / $tripRevenue) * 100, 1) : 0;

        $topTrips = $trips->map(function ($trip) {
            $orderRevenue = (float) ($trip->order?->price ?? 0);
            $tripExp = (float) Expense::where('trip_id', $trip->id)
                ->whereIn('status', ['approved', 'paid'])
                ->sum('amount');
            return [
                'id' => $trip->id,
                'reference' => $trip->reference,
                'vehicle' => $trip->vehicle_plate_snapshot,
                'driver' => $trip->driver_name_snapshot,
                'revenue' => $orderRevenue,
                'expenses' => $tripExp,
                'profit' => $orderRevenue - $tripExp,
                'margin' => $orderRevenue > 0 ? round((($orderRevenue - $tripExp) / $orderRevenue) * 100, 1) : 0,
            ];
        })->sortByDesc('profit')->take(10)->values();

        return [
            'total_trips' => $totalTrips,
            'total_revenue' => $tripRevenue,
            'total_expenses' => $tripExpenses,
            'total_profit' => $profit,
            'profit_margin' => $margin,
            'avg_revenue_per_trip' => $totalTrips > 0 ? round($tripRevenue / $totalTrips, 2) : 0,
            'avg_expense_per_trip' => $totalTrips > 0 ? round($tripExpenses / $totalTrips, 2) : 0,
            'top_trips' => $topTrips,
        ];
    }

    /**
     * Profitability report — revenue (orders) minus costs (approved/paid expenses) per delivered trip,
     * grouped by an arbitrary dimension (truck, trip, driver, dispatcher, route, client, month).
     */
    public function profitabilityReport(array $filters = []): array
    {
        $from = !empty($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : now()->startOfMonth();
        $to = !empty($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : now();
        $groupBy = $filters['group_by'] ?? 'truck';

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

        $trips = $query->get();
        $tripIds = $trips->pluck('id')->filter();

        $expenseTotals = [];
        if ($tripIds->isNotEmpty()) {
            $expenseTotals = Expense::whereIn('trip_id', $tripIds)
                ->whereIn('status', ['approved', 'paid'])
                ->selectRaw('trip_id, SUM(amount) as total')
                ->groupBy('trip_id')
                ->pluck('total', 'trip_id');
        }

        $groups = [];
        $trend = [];
        foreach ($trips as $trip) {
            $revenue = (float) ($trip->order_price ?? 0);
            $expenses = (float) ($expenseTotals[$trip->id] ?? 0);

            [$key, $label] = $this->profitabilityGroupFor($trip, $groupBy);
            if (!isset($groups[$key])) {
                $groups[$key] = ['label' => $label, 'trips' => 0, 'revenue' => 0, 'expenses' => 0];
            }
            $groups[$key]['trips']++;
            $groups[$key]['revenue'] += $revenue;
            $groups[$key]['expenses'] += $expenses;

            $monthKey = $trip->created_at?->format('Y-m') ?? 'n/a';
            if (!isset($trend[$monthKey])) {
                $trend[$monthKey] = ['revenue' => 0, 'expenses' => 0];
            }
            $trend[$monthKey]['revenue'] += $revenue;
            $trend[$monthKey]['expenses'] += $expenses;
        }

        $breakdown = collect($groups)
            ->map(fn ($g) => [
                'label' => $g['label'],
                'trips' => (int) $g['trips'],
                'revenue' => round($g['revenue'], 2),
                'expenses' => round($g['expenses'], 2),
                'profit' => round($g['revenue'] - $g['expenses'], 2),
                'margin' => $g['revenue'] > 0
                    ? round((($g['revenue'] - $g['expenses']) / $g['revenue']) * 100, 1)
                    : 0,
            ])
            ->sortByDesc('profit')
            ->values()
            ->all();

        $totalRevenue = array_sum(array_column($groups, 'revenue'));
        $totalExpenses = array_sum(array_column($groups, 'expenses'));
        $totalProfit = $totalRevenue - $totalExpenses;
        $margin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        $trendSeries = [];
        for ($d = $from->copy()->startOfMonth(); $d->lte($to); $d->addMonth()) {
            $key = $d->format('Y-m');
            $revenue = $trend[$key]['revenue'] ?? 0;
            $expenses = $trend[$key]['expenses'] ?? 0;
            $trendSeries[] = [
                'period' => $d->format('M Y'),
                'revenue' => round($revenue, 2),
                'expenses' => round($expenses, 2),
                'profit' => round($revenue - $expenses, 2),
            ];
        }

        $costBreakdown = [];
        if ($tripIds->isNotEmpty()) {
            $costBreakdown = Expense::whereIn('expenses.trip_id', $tripIds)
                ->whereIn('expenses.status', ['approved', 'paid'])
                ->leftJoin('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
                ->selectRaw('COALESCE(expense_types.name, "Uncategorized") as name, SUM(expenses.amount) as total')
                ->groupBy('name')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($c) => ['name' => $c->name, 'total' => (float) $c->total])
                ->values()
                ->all();
        }

        $profitableGroups = collect($breakdown)->where('profit', '>', 0)->values();
        $lossGroups = collect($breakdown)->where('profit', '<', 0)->values();

        return [
            'group_by' => $groupBy,
            'filters' => array_filter($filters, fn ($v) => $v !== '' && $v !== null),
            'summary' => [
                'total_trips' => (int) $trips->count(),
                'total_revenue' => round($totalRevenue, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_profit' => round($totalProfit, 2),
                'profit_margin' => $margin,
                'avg_revenue_per_trip' => $trips->isNotEmpty() ? round($totalRevenue / $trips->count(), 2) : 0,
                'avg_expense_per_trip' => $trips->isNotEmpty() ? round($totalExpenses / $trips->count(), 2) : 0,
                'avg_profit_per_trip' => $trips->isNotEmpty() ? round($totalProfit / $trips->count(), 2) : 0,
                'profitable_groups' => $profitableGroups->count(),
                'loss_groups' => $lossGroups->count(),
            ],
            'breakdown' => $breakdown,
            'trend' => $trendSeries,
            'cost_breakdown' => $costBreakdown,
            'top_profitable' => $profitableGroups->take(5)->all(),
            'top_losses' => $lossGroups->take(5)->all(),
        ];
    }

    private function profitabilityGroupFor($trip, string $groupBy): array
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

    public function unifiedReport(?int $selectedCurrencyId = null): array
    {
        $currencyService = app(CurrencyService::class);
        $currencies = Currency::all(['id', 'code', 'name', 'symbol', 'is_default']);
        $defaultCurrency = $currencies->firstWhere('is_default', true) ?? $currencies->first();
        $selectedCurrency = $currencies->firstWhere('id', $selectedCurrencyId) ?? $defaultCurrency;

        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $twelveMonthsAgo = $today->copy()->subMonths(12);

        $expenseReport = $this->expenseReport($twelveMonthsAgo, $today);
        $containerReport = $this->containerReport();
        $performanceReport = $this->performanceReport();
        $walletReport = $this->walletReport();
        $invoiceReport = $this->invoiceReport();
        $tripProfitability = $this->tripProfitabilityReport($monthStart, $today);

        // Convert financials to selected currency
        $convert = fn ($val, $from = null) => $currencyService->convert(
            $val,
            $from ?? $this->usd,
            $selectedCurrency
        );

        $expenseReport['total'] = $convert($expenseReport['total']);
        $expenseReport['pending'] = $convert($expenseReport['pending']);
        $expenseReport['by_category'] = $expenseReport['by_category']->map(fn ($c) => array_merge($c, [
            'total' => $convert($c['total']),
        ]))->toArray();
        $expenseReport['by_vehicle'] = $expenseReport['by_vehicle']->map(fn ($v) => array_merge($v, [
            'total' => $convert($v['total']),
        ]))->toArray();
        $expenseReport['monthly_series'] = array_map(fn ($v) => $convert($v), $expenseReport['monthly_series']);

        $containerReport['total_penalties'] = $convert($containerReport['total_penalties_raw'], $this->rwf);
        $containerReport['recent_penalties'] = $convert($containerReport['recent_penalties_raw'], $this->rwf);

        $tripProfitability['total_revenue'] = $convert($tripProfitability['total_revenue']);
        $tripProfitability['total_expenses'] = $convert($tripProfitability['total_expenses']);
        $tripProfitability['total_profit'] = $convert($tripProfitability['total_profit']);
        $tripProfitability['avg_revenue_per_trip'] = $convert($tripProfitability['avg_revenue_per_trip']);
        $tripProfitability['avg_expense_per_trip'] = $convert($tripProfitability['avg_expense_per_trip']);
        $tripProfitability['top_trips'] = $tripProfitability['top_trips']->map(fn ($t) => array_merge($t, [
            'revenue' => $convert($t['revenue']),
            'expenses' => $convert($t['expenses']),
            'profit' => $convert($t['profit']),
        ]))->toArray();

        $invoiceReport['total_outstanding'] = $convert($invoiceReport['total_outstanding']);
        $invoiceReport['total_paid'] = $convert($invoiceReport['total_paid']);
        $invoiceReport['overdue'] = $convert($invoiceReport['overdue']);
        $invoiceReport['monthly_series'] = array_map(fn ($v) => $convert($v), $invoiceReport['monthly_series']);
        $invoiceReport['status_distribution'] = $invoiceReport['status_distribution']->map(fn ($i) => array_merge($i, [
            'amount' => $convert($i['amount']),
        ]))->toArray();

        return [
            'containers' => $containerReport,
            'expenses' => $expenseReport,
            'performance' => $performanceReport,
            'wallets' => $walletReport,
            'invoices' => $invoiceReport,
            'trip_profitability' => $tripProfitability,
            'currencies' => $currencies->values(),
            'selected_currency' => [
                'id' => $selectedCurrency->id,
                'code' => $selectedCurrency->code,
                'symbol' => $selectedCurrency->symbol,
            ],
            'last_updated' => $today->toISOString(),
        ];
    }
}
