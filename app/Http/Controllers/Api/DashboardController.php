<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\Trip;
use App\Models\TrafficFine;
use App\Models\Trailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard overview for portal home page
     */
    public function getOverview()
    {
        $today = now();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        // Fleet Status Overview - Real Database Queries
        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();
        $inactiveVehicles = Vehicle::where('status', 'inactive')->count();
        
        // Get vehicles without drivers (active vehicles that have no current driver assignment)
        $vehiclesWithoutDrivers = DB::table('vehicles')
            ->leftJoin('driver_vehicle_assignments', function($join) {
                $join->on('vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
                     ->whereNull('driver_vehicle_assignments.end_date');
            })
            ->where('vehicles.status', 'active')
            ->whereNull('driver_vehicle_assignments.driver_id')
            ->count();

        $fleetStatus = [
            'total_vehicles' => $totalVehicles,
            'active_vehicles' => $activeVehicles,
            'maintenance_vehicles' => $maintenanceVehicles,
            'inactive_vehicles' => $inactiveVehicles,
            'vehicles_without_drivers' => $vehiclesWithoutDrivers,
            'total_trailers' => Trailer::count(),
            'active_trailers' => Trailer::where('status', 'active')->count(),
        ];

        // Driver Status - Real Database Queries
        $totalDrivers = Driver::count();
        $assignedDrivers = DB::table('driver_vehicle_assignments')
            ->whereNull('end_date')
            ->distinct('driver_id')
            ->count();
        $availableDrivers = $totalDrivers - $assignedDrivers;

        $driverStatus = [
            'total_drivers' => $totalDrivers,
            'assigned_drivers' => $assignedDrivers,
            'available_drivers' => $availableDrivers,
        ];

        // Order & Trip Statistics
        $orderStats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'draft')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'in_transit_orders' => Order::where('status', 'in_transit')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'today_orders' => Order::whereDate('created_at', $today)->count(),
        ];

        $tripStats = [
            'total_trips' => Trip::count(),
            'pending_trips' => Trip::where('status', 'pending')->count(),
            'assigned_trips' => Trip::where('status', 'assigned')->count(),
            'on_route_trips' => Trip::where('status', 'on_route')->count(),
            'delivered_trips' => Trip::where('status', 'delivered')->count(),
            'today_trips' => Trip::whereDate('created_at', $today)->count(),
            'active_trips' => Trip::whereIn('status', ['assigned', 'on_route'])->count(),
        ];

        // Fine Management
        $fineStats = [
            'total_fines' => TrafficFine::count(),
            'pending_fines' => TrafficFine::where('status', 'PENDING')->count(),
            'paid_fines' => TrafficFine::where('status', 'PAID')->count(),
            'total_amount_due' => TrafficFine::where('status', 'PENDING')->sum('ticket_amount'),
            'total_paid' => TrafficFine::where('status', 'PAID')->sum('paid_amount'),
            'overdue_fines' => TrafficFine::where('status', 'PENDING')
                ->where('pay_by', '<', $today)
                ->count(),
        ];

        // Recent Activity
        $recentActivity = [
            'recent_orders' => Order::with('client')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(['id', 'reference', 'client_id', 'status', 'created_at']),
            'recent_trips' => Trip::with(['order.client'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(['id', 'order_id', 'status', 'vehicle_plate_snapshot', 'driver_name_snapshot', 'created_at']),
            'recent_fines' => TrafficFine::orderBy('created_at', 'desc')
                ->take(5)
                ->get(['id', 'ticket_number', 'plate_number', 'ticket_amount', 'status', 'issued_at']),
        ];

        // Performance Metrics (Weekly)
        $weeklyPerformance = [
            'weekly_orders' => Order::whereBetween('created_at', [$weekStart, $today])->count(),
            'weekly_trips' => Trip::whereBetween('created_at', [$weekStart, $today])->count(),
            'weekly_delivered' => Trip::where('status', 'delivered')
                ->whereBetween('created_at', [$weekStart, $today])
                ->count(),
            'delivery_rate' => $this->calculateDeliveryRate($weekStart, $today),
        ];

        // Fleet Utilization Metrics - Real Calculations
        $utilizationRate = $totalVehicles > 0 ? round(($activeVehicles / $totalVehicles) * 100, 1) : 0;
        $driverAllocationRate = $totalDrivers > 0 ? round(($assignedDrivers / $totalDrivers) * 100, 1) : 0;
        $availableNow = $activeVehicles - $vehiclesWithoutDrivers;

        $fleetUtilization = [
            'utilization_rate' => $utilizationRate,
            'driver_allocation_rate' => $driverAllocationRate,
            'available_now' => $availableNow,
            'avg_downtime' => 12, // This could be calculated from maintenance logs later
        ];

        // Fuel Management Data - Using ControlTower patterns
        $dailyFuel = DB::table('telemetry_events')
            ->whereDate('occurred_at', $today)
            ->selectRaw("SUM(CASE WHEN type = 'fuel_refill' THEN value ELSE 0 END) as total_refilled")
            ->selectRaw("SUM(CASE WHEN type = 'fuel_drain' THEN value ELSE 0 END) as total_stolen")
            ->first();

        $totalFleetFuelLiters = DB::table('vehicle_snapshots')
            ->join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.status', 'active')
            ->where('vehicle_snapshots.fuel_level', '>=', 0)
            ->sum('vehicle_snapshots.fuel_level');

        // Get vehicles with critical fuel levels
        $criticalFuelVehicles = DB::table('vehicle_snapshots')
            ->join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
            ->where('vehicle_snapshots.low_fuel', true)
            ->where('vehicle_snapshots.fuel_level', '>', 0)
            ->where('vehicles.status', 'active')
            ->select(
                'vehicles.plate_number',
                'vehicle_snapshots.fuel_level as fuel_percentage'
            )
            ->get();

        // Get recent fuel thefts/drainage
        $fuelDrainageVehicles = DB::table('telemetry_events')
            ->join('vehicles', 'telemetry_events.vehicle_id', '=', 'vehicles.id')
            ->where('telemetry_events.type', 'fuel_drain')
            ->whereDate('telemetry_events.occurred_at', $today)
            ->select(
                'vehicles.plate_number',
                'telemetry_events.value as drained_amount'
            )
            ->get();

        // Calculate vehicles with high consumption (simplified - could be enhanced with historical data)
        $highConsumptionVehicles = DB::table('vehicle_snapshots')
            ->join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
            ->where('vehicle_snapshots.fuel_level', '<', 25) // Less than 25% fuel
            ->where('vehicles.status', 'active')
            ->where('vehicle_snapshots.last_seen_at', '>', now()->subHours(2)) // Active in last 2 hours
            ->select(
                'vehicles.plate_number',
                DB::raw('ROUND((100 - vehicle_snapshots.fuel_level) * 1.5) as consumption_rate')
            )
            ->get();

        // Calculate fuel status distribution
        $fuelStatusDistribution = DB::table('vehicle_snapshots')
            ->join('vehicles', 'vehicle_snapshots.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.status', 'active')
            ->selectRaw("
                SUM(CASE WHEN fuel_level <= 10 THEN 1 ELSE 0 END) as critical_count,
                SUM(CASE WHEN fuel_level > 10 AND fuel_level <= 25 THEN 1 ELSE 0 END) as low_count,
                SUM(CASE WHEN fuel_level > 25 AND fuel_level <= 50 THEN 1 ELSE 0 END) as medium_count,
                SUM(CASE WHEN fuel_level > 50 AND fuel_level <= 75 THEN 1 ELSE 0 END) as good_count,
                SUM(CASE WHEN fuel_level > 75 THEN 1 ELSE 0 END) as full_count,
                AVG(fuel_level) as avg_fuel_level
            ")
            ->first();

        $fuelManagement = [
            'total_fuel_capacity' => round($totalFleetFuelLiters),
            'total_vehicles' => $totalVehicles,
            'vehicles_critical_fuel' => $criticalFuelVehicles->count(),
            'vehicles_high_consumption' => $highConsumptionVehicles->count(),
            'vehicles_fuel_drainage' => $fuelDrainageVehicles->count(),
            'vehicles_efficient' => $activeVehicles - $highConsumptionVehicles->count() - $criticalFuelVehicles->count(),
            'total_consumed' => $dailyFuel->total_stolen ?? 0,
            'total_filled' => $dailyFuel->total_refilled ?? 0,
            'net_consumption' => ($dailyFuel->total_stolen ?? 0),
            'avg_fuel_level' => round($fuelStatusDistribution->avg_fuel_level ?? 0),
            'avg_efficiency' => 28, // Could be calculated from trip data
            'critical_fuel_vehicles' => $criticalFuelVehicles->take(4)->map(function($vehicle) {
                return [
                    'plate' => $vehicle->plate_number,
                    'fuel_percentage' => round($vehicle->fuel_percentage)
                ];
            })->toArray(),
            'high_consumption_vehicles' => $highConsumptionVehicles->take(7)->map(function($vehicle) {
                return [
                    'plate' => $vehicle->plate_number,
                    'consumption_rate' => round($vehicle->consumption_rate)
                ];
            })->toArray(),
            'fuel_drainage_vehicles' => $fuelDrainageVehicles->take(2)->map(function($vehicle) {
                return [
                    'plate' => $vehicle->plate_number,
                    'drained_amount' => round($vehicle->drained_amount)
                ];
            })->toArray(),
            'fuel_status_distribution' => [
                ['label' => 'Critical (<10%)', 'count' => $fuelStatusDistribution->critical_count, 'color' => '#ef4444'],
                ['label' => 'Low (10-25%)', 'count' => $fuelStatusDistribution->low_count, 'color' => '#f59e0b'],
                ['label' => 'Medium (25-50%)', 'count' => $fuelStatusDistribution->medium_count, 'color' => '#3b82f6'],
                ['label' => 'Good (50-75%)', 'count' => $fuelStatusDistribution->good_count, 'color' => '#10b981'],
                ['label' => 'Full (>75%)', 'count' => $fuelStatusDistribution->full_count, 'color' => '#059669']
            ]
        ];

        // Support System Data
        $supportStats = DB::table('support_tickets')
            ->selectRaw("
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting_tickets,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_tickets,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tickets,
                AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_resolution_hours
            ")
            ->whereDate('created_at', $today)
            ->first();

        $recentSupportTickets = DB::table('support_tickets')
            ->join('support_categories', 'support_tickets.support_category_id', '=', 'support_categories.id')
            ->leftJoin('users', 'support_tickets.assigned_to', '=', 'users.id')
            ->select([
                'support_tickets.id',
                'support_tickets.reference',
                'support_tickets.title',
                'support_tickets.priority',
                'support_tickets.status',
                'support_tickets.created_at',
                'support_categories.name as category_name',
                'users.name as assigned_to_name'
            ])
            ->orderBy('support_tickets.created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($ticket) {
                return [
                    'id' => $ticket->id,
                    'reference' => $ticket->reference,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'category_name' => $ticket->category_name,
                    'assigned_to' => $ticket->assigned_to_name,
                    'time_ago' => \Carbon\Carbon::parse($ticket->created_at)->diffForHumans()
                ];
            });

        $supportCategories = DB::table('support_categories')
            ->select('name', DB::raw('COUNT(support_tickets.id) as ticket_count'))
            ->leftJoin('support_tickets', 'support_categories.id', '=', 'support_tickets.support_category_id')
            ->where('support_categories.is_active', true)
            ->groupBy('support_categories.id', 'support_categories.name')
            ->get()
            ->map(function($category) {
                return [
                    'name' => $category->name,
                    'count' => $category->ticket_count
                ];
            });

        $supportOverview = [
            'total_tickets' => (int) ($supportStats->total_tickets ?? 0),
            'open_tickets' => (int) ($supportStats->open_tickets ?? 0),
            'in_progress_tickets' => (int) ($supportStats->in_progress_tickets ?? 0),
            'waiting_tickets' => (int) ($supportStats->waiting_tickets ?? 0),
            'resolved_tickets' => (int) ($supportStats->resolved_tickets ?? 0),
            'urgent_tickets' => (int) ($supportStats->urgent_tickets ?? 0),
            'high_priority_tickets' => (int) ($supportStats->high_priority_tickets ?? 0),
            'avg_resolution_hours' => round($supportStats->avg_resolution_hours ?? 0, 1),
            'categories' => $supportCategories,
            'recent_tickets' => $recentSupportTickets
        ];

        return response()->json([
            'fleet_status' => $fleetStatus,
            'driver_status' => $driverStatus,
            'fleet_utilization' => $fleetUtilization,
            'fuel_management' => $fuelManagement,
            'support_overview' => $supportOverview,
            'order_stats' => $orderStats,
            'trip_stats' => $tripStats,
            'fine_stats' => $fineStats,
            'recent_activity' => $recentActivity,
            'weekly_performance' => $weeklyPerformance,
            'last_updated' => $today->toISOString(),
        ]);
    }

    /**
     * Get detailed analytics for dashboard page
     */
    public function getAnalytics(Request $request)
    {
        $period = $request->get('period', 'week'); // day, week, month
        $startDate = $this->getStartDate($period);
        $today = now();

        // Trip Timeline Data
        $tripTimeline = Trip::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $today])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Order Status Distribution
        $orderStatusDistribution = Order::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $today])
            ->groupBy('status')
            ->get();

        // Trip Status Distribution
        $tripStatusDistribution = Trip::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $today])
            ->groupBy('status')
            ->get();

        // Fleet Utilization
        $fleetUtilization = [
            'vehicle_utilization' => $this->calculateVehicleUtilization($startDate, $today),
            'driver_utilization' => $this->calculateDriverUtilization($startDate, $today),
        ];

        // Top Performing Vehicles
        $topVehicles = DB::table('trips')
            ->select('vehicle_plate_snapshot', DB::raw('COUNT(*) as trip_count'))
            ->whereBetween('created_at', [$startDate, $today])
            ->whereNotNull('vehicle_plate_snapshot')
            ->groupBy('vehicle_plate_snapshot')
            ->orderByDesc('trip_count')
            ->limit(10)
            ->get();

        // Fine Trends
        $fineTrends = TrafficFine::selectRaw('DATE(issued_at) as date, COUNT(*) as count, SUM(ticket_amount) as total_amount')
            ->whereBetween('issued_at', [$startDate, $today])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Compliance Status
        $complianceStatus = [
            'insurance_expiring' => $this->getExpiringInsurances(),
            'inspection_overdue' => $this->getOverdueInspections(),
            'driver_docs_expiring' => $this->getExpiringDriverDocuments(),
        ];

        return response()->json([
            'trip_timeline' => $tripTimeline,
            'order_status_distribution' => $orderStatusDistribution,
            'trip_status_distribution' => $tripStatusDistribution,
            'fleet_utilization' => $fleetUtilization,
            'top_vehicles' => $topVehicles,
            'fine_trends' => $fineTrends,
            'compliance_status' => $complianceStatus,
            'period' => $period,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $today->toDateString(),
            ],
        ]);
    }

    /**
     * Get real-time operational status
     */
    public function getOperationalStatus()
    {
        $today = now();
        
        // Current active trips
        $activeTrips = Trip::whereIn('status', ['assigned', 'on_route'])
            ->with(['order.client', 'driver.user'])
            ->get(['id', 'order_id', 'driver_id', 'status', 'vehicle_plate_snapshot', 'driver_name_snapshot', 'departure_time']);

        // Vehicles needing attention
        $vehiclesNeedingAttention = [
            'low_fuel' => $this->getLowFuelVehicles(),
            'long_stationary' => $this->getLongStationaryVehicles(),
            'maintenance_due' => $this->getMaintenanceDueVehicles(),
        ];

        // Urgent orders
        $urgentOrders = Order::where('status', 'confirmed')
            ->where('pickup_date', '<=', $today->copy()->addDays(3))
            ->with('client')
            ->orderBy('pickup_date')
            ->get(['id', 'reference', 'client_id', 'origin', 'destination', 'pickup_date']);

        return response()->json([
            'active_trips' => $activeTrips,
            'vehicles_needing_attention' => $vehiclesNeedingAttention,
            'urgent_orders' => $urgentOrders,
            'timestamp' => $today->toISOString(),
        ]);
    }

    // Helper methods
    private function calculateDeliveryRate($startDate, $endDate)
    {
        $totalTrips = Trip::whereBetween('created_at', [$startDate, $endDate])->count();
        $deliveredTrips = Trip::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        return $totalTrips > 0 ? round(($deliveredTrips / $totalTrips) * 100, 2) : 0;
    }

    private function calculateVehicleUtilization($startDate, $endDate)
    {
        $totalVehicles = Vehicle::count();
        $activeVehicles = Trip::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('vehicle_id')
            ->distinct('vehicle_id')
            ->count('vehicle_id');
        
        return $totalVehicles > 0 ? round(($activeVehicles / $totalVehicles) * 100, 2) : 0;
    }

    private function calculateDriverUtilization($startDate, $endDate)
    {
        $totalDrivers = Driver::count();
        $activeDrivers = Trip::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('driver_id')
            ->distinct('driver_id')
            ->count('driver_id');
        
        return $totalDrivers > 0 ? round(($activeDrivers / $totalDrivers) * 100, 2) : 0;
    }

    private function getStartDate($period)
    {
        switch ($period) {
            case 'day':
                return now()->startOfDay();
            case 'week':
                return now()->startOfWeek();
            case 'month':
                return now()->startOfMonth();
            default:
                return now()->startOfWeek();
        }
    }

    private function getExpiringInsurances()
    {
        return DB::table('vehicle_insurances')
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_insurances.vehicle_id')
            ->where('vehicle_insurances.expiry_date', '<=', now()->addDays(30))
            ->where('vehicle_insurances.expiry_date', '>', now())
            ->select('vehicles.plate_number', 'vehicle_insurances.expiry_date')
            ->get();
    }

    private function getOverdueInspections()
    {
        return DB::table('vehicle_inspections')
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_inspections.vehicle_id')
            ->where('vehicle_inspections.next_inspection_date', '<', now())
            ->select('vehicles.plate_number', 'vehicle_inspections.next_inspection_date')
            ->get();
    }

    private function getExpiringDriverDocuments()
    {
        $thirtyDaysFromNow = now()->addDays(30);
        
        $expiringLicenses = Driver::whereDate('driving_licence_expiry', '<=', $thirtyDaysFromNow)
            ->whereDate('driving_licence_expiry', '>', now())
            ->with('user:name,id')
            ->get(['user_id', 'driving_licence_expiry']);

        $expiringPassports = Driver::whereDate('passport_expiry', '<=', $thirtyDaysFromNow)
            ->whereDate('passport_expiry', '>', now())
            ->with('user:name,id')
            ->get(['user_id', 'passport_expiry']);

        return [
            'licenses' => $expiringLicenses,
            'passports' => $expiringPassports,
        ];
    }

    private function getLowFuelVehicles()
    {
        return Vehicle::where('current_fuel_percentage', '<', 15)
            ->get(['plate_number', 'current_fuel_percentage']);
    }

    private function getLongStationaryVehicles()
    {
        return Vehicle::whereNotNull('stationary_at')
            ->where('stationary_at', '<', now()->subHours(24))
            ->get(['plate_number', 'stationary_at']);
    }

    private function getMaintenanceDueVehicles()
    {
        return Vehicle::where('status', 'active')
            ->where('next_maintenance_date', '<=', now()->addDays(7))
            ->get(['plate_number', 'next_maintenance_date']);
    }
}
