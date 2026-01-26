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
        $today = now();

        // Insurance Stats
        $insurances = \App\Models\VehicleInsurance::all();
        $groundedIns = $insurances->filter(fn($i) => \Carbon\Carbon::parse($i->expiry_date)->isPast())->count();

        // Inspection Stats
        $inspections = \App\Models\VehicleInspection::all();
        $groundedInsp = $inspections->filter(fn($i) => \Carbon\Carbon::parse($i->completed_date)->isPast())->count();

        // Total Fleet
        $totalVehicles = \App\Models\Vehicle::count();
        $groundedTotal = \App\Models\VehicleInsurance::where('expiry_date', '<', $today)
            ->orWhereHas('vehicle.inspections', function($q) use ($today) {
                $q->where('completed_date', '<', $today);
            })->distinct('vehicle_id')->count();

        return response()->json([
            'total_vehicles' => $totalVehicles,
            'grounded_total' => $groundedTotal,
            'insurance_alerts' => $groundedIns,
            'inspection_alerts' => $groundedInsp,
            'health_percentage' => $totalVehicles > 0 ? round((($totalVehicles - $groundedTotal) / $totalVehicles) * 100) : 100
        ]);
    }
}
