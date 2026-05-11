<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Order;
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

        // Fleet Status Overview
        $fleetStatus = [
            'total_vehicles' => Vehicle::count(),
            'active_vehicles' => Vehicle::where('status', 'active')->count(),
            'maintenance_vehicles' => Vehicle::where('status', 'maintenance')->count(),
            'inactive_vehicles' => Vehicle::where('status', 'inactive')->count(),
            'total_trailers' => Trailer::count(),
            'active_trailers' => Trailer::where('status', 'active')->count(),
        ];

        // Driver Status
        $driverStatus = [
            'total_drivers' => Driver::count(),
            'assigned_drivers' => DB::table('driver_vehicle_assignments')
                ->whereNull('end_date')
                ->distinct('driver_id')
                ->count(),
            'available_drivers' => Driver::whereDoesntHave('vehicleAssignments', function($query) {
                $query->whereNull('end_date');
            })->count(),
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

        return response()->json([
            'fleet_status' => $fleetStatus,
            'driver_status' => $driverStatus,
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
