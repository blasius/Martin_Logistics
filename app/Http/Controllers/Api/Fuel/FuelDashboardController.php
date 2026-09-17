<?php

namespace App\Http\Controllers\Api\Fuel;

use App\Http\Controllers\Controller;
use App\Models\VehicleRouteFuelRatio;
use App\Models\Vehicle;
use App\Services\FuelManagementService;
use App\Services\SupportAutoTicketService;
use Illuminate\Http\Request;

class FuelDashboardController extends Controller
{
    public function __construct(protected FuelManagementService $fuelService) {}

    public function index()
    {
        return response()->json($this->fuelService->dashboardStats());
    }

    public function overview(Request $request)
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        return response()->json($this->fuelService->fuelLifecycleOverview($validated['days'] ?? 30));
    }

    public function reconcile(Request $request, SupportAutoTicketService $tickets)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'open_tickets' => 'nullable|boolean',
        ]);

        $report = $this->fuelService->reconcileFuel($validated['date_from'] ?? null, $validated['date_to'] ?? null);

        $material = collect($report['vehicles'])->filter(fn ($r) => $r['escalate']);
        $opened = [];

        if (($validated['open_tickets'] ?? false) && $material->isNotEmpty()) {
            foreach ($material as $row) {
                $vehicle = Vehicle::find($row['vehicle_id']);
                $ticket = $tickets->open(
                    [
                        'title' => "Material fuel variance — {$row['plate_number']}",
                        'description' => $this->describeVariance($row),
                        'priority' => abs($row['variance_liters']) >= 100 ? 'high' : 'normal',
                        'source' => 'auto_fuel_flag',
                    ],
                    $vehicle,
                    'Fuel'
                );

                $opened[] = [
                    'ticket_id' => $ticket->id,
                    'reference' => $ticket->reference,
                    'plate_number' => $row['plate_number'],
                    'new' => $ticket->wasRecentlyCreated,
                ];
            }
        }

        return response()->json([
            ...$report,
            'tickets_opened' => $opened,
        ]);
    }

    private function describeVariance(array $row): string
    {
        $direction = $row['variance_liters'] >= 0
            ? 'Wialon detected more fuel being added than was recorded as dispensed'
            : 'recorded dispenses exceed Wialon-detected fills';

        return sprintf(
            "Auto-generated during fuel reconciliation.\n\n%s.\n\nPlate: %s\nPeriod refills (Wialon): %.2f L\nPeriod recorded dispenses: %.2f L\nVariance: %.2f L (%.2f%%)\nOverrides: %d (%.2f L)\n",
            $direction,
            $row['plate_number'],
            $row['wialon_refills'],
            $row['recorded_dispenses'],
            $row['variance_liters'],
            $row['variance_percent'],
            $row['override_count'],
            $row['override_liters']
        );
    }

    public function ratios(Request $request)
    {
        $query = VehicleRouteFuelRatio::with(['vehicle:id,plate_number,make,model', 'route:id,name']);

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        return $query->orderByDesc('created_at')->paginate($request->per_page ?? 50);
    }

    public function storeRatio(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'km_per_liter' => 'required|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'notes' => 'nullable|string',
        ]);

        return VehicleRouteFuelRatio::create($validated);
    }

    public function deleteRatio(VehicleRouteFuelRatio $vehicleRouteFuelRatio)
    {
        $vehicleRouteFuelRatio->delete();
        return response()->json(['message' => 'Ratio deleted']);
    }
}
