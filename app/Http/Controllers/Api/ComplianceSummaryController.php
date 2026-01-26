<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Trailer;
use Carbon\Carbon;

class ComplianceSummaryController extends Controller
{
    public function complianceSummary()
    {
        $today = now()->startOfDay();

        // Process Truck fleet
        $vehicles = Vehicle::with(['insurances', 'inspections', 'trafficFines'])->get()
            ->map(fn($u) => $this->mapCompliance($u, 'Truck', $today));

        // Process Trailer fleet
        $trailers = Trailer::with(['insurances', 'inspections', 'trafficFines'])->get()
            ->map(fn($u) => $this->mapCompliance($u, 'Trailer', $today));

        $fullFleet = $vehicles->concat($trailers);

        return response()->json([
            'stats' => [
                'total_units' => $fullFleet->count(),
                'grounded_count' => $fullFleet->where('is_grounded', true)->count(),
                'insurance_alerts' => $fullFleet->filter(fn($u) => $u['issues']['insurance'])->count(),
                'inspection_alerts' => $fullFleet->filter(fn($u) => $u['issues']['inspection'])->count(),
                'fine_alerts' => $fullFleet->filter(fn($u) => $u['issues']['fines'])->count(),
                'health_percentage' => $fullFleet->count() > 0
                    ? round(($fullFleet->where('is_grounded', false)->count() / $fullFleet->count()) * 100)
                    : 100,
            ],
            'grounded_list' => $fullFleet->where('is_grounded', true)->values()
        ]);
    }

    private function mapCompliance($unit, $label, $today)
    {
        $hasExpiredIns = $unit->insurances->where('expiry_date', '<', $today->toDateString())->isNotEmpty();
        $hasExpiredInsp = $unit->inspections->where('completed_date', '<', $today->toDateString())->isNotEmpty();
        $hasOverdueFine = $unit->trafficFines->where('status', 'PENDING')
            ->where('pay_by', '<', $today->toDateString())
            ->isNotEmpty();

        return [
            'plate' => $unit->plate_number,
            'type' => $label,
            'is_grounded' => ($hasExpiredIns || $hasExpiredInsp || $hasOverdueFine),
            'issues' => [
                'insurance' => $hasExpiredIns,
                'inspection' => $hasExpiredInsp,
                'fines' => $hasOverdueFine,
            ]
        ];
    }
}
