<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleInspection;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ComplianceSummaryController extends Controller
{
    public function complianceSummary()
    {
        $today = now()->startOfDay();

        // 1. Module-Specific Alert Counts
        $insuranceAlerts = \App\Models\VehicleInsurance::where('expiry_date', '<', $today)->count();
        $inspectionAlerts = \App\Models\VehicleInspection::where('completed_date', '<', $today)->count();

        // 2. Traffic Fine Alerts (Mapped to your specific migration status 'PENDING')
        // Note: I updated the status check to 'PENDING' to match your migration default
        $fineAlerts = \App\Models\TrafficFine::where('status', 'PENDING')
            ->where('pay_by', '<', $today)
            ->count();

        // 3. Unique Grounded Vehicles
        $totalVehicles = \App\Models\Vehicle::count();

        // Get IDs from Insurance
        $insIds = \App\Models\VehicleInsurance::where('expiry_date', '<', $today)->pluck('vehicle_id');

        // Get IDs from Inspections
        $inspIds = \App\Models\VehicleInspection::where('completed_date', '<', $today)->pluck('vehicle_id');

        // Get IDs from Traffic Fines (Fixing the polymorphic column name here)
        $fineIds = \App\Models\TrafficFine::where('fineable_type', \App\Models\Vehicle::class)
            ->where('status', 'PENDING')
            ->where('pay_by', '<', $today)
            ->pluck('fineable_id'); // This was where vehicle_id was missing

        $groundedVehicleIds = collect()
            ->concat($insIds)
            ->concat($inspIds)
            ->concat($fineIds)
            ->unique();

        $groundedCount = $groundedVehicleIds->count();

        return response()->json([
            'total_vehicles' => $totalVehicles,
            'grounded_total' => $groundedCount,
            'alerts' => [
                'insurance' => $insuranceAlerts,
                'inspections' => $inspectionAlerts,
                'fines' => $fineAlerts,
            ],
            'health_percentage' => $totalVehicles > 0
                ? round((($totalVehicles - $groundedCount) / $totalVehicles) * 100)
                : 100
        ]);
    }
}
