<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;

class MaintenanceScheduleController extends Controller
{
    public function __construct(protected MaintenanceService $maintenanceService) {}

    public function index(Request $request)
    {
        $query = MaintenanceSchedule::with('vehicle:id,plate_number,make,model');

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|in:oil_change,tire_rotation,brake_check,inspection,general',
            'interval_km' => 'nullable|numeric|min:0',
            'interval_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        return MaintenanceSchedule::create($validated);
    }

    public function show(MaintenanceSchedule $maintenanceSchedule)
    {
        return $maintenanceSchedule->load('vehicle:id,plate_number,make,model');
    }

    public function update(Request $request, MaintenanceSchedule $maintenanceSchedule)
    {
        $validated = $request->validate([
            'type' => 'required|in:oil_change,tire_rotation,brake_check,inspection,general',
            'interval_km' => 'nullable|numeric|min:0',
            'interval_days' => 'nullable|integer|min:0',
            'last_done_at' => 'nullable|date',
            'last_done_km' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $maintenanceSchedule->update($validated);
        return $maintenanceSchedule;
    }

    public function destroy(MaintenanceSchedule $maintenanceSchedule)
    {
        $maintenanceSchedule->delete();
        return response()->json(['message' => 'Schedule deleted']);
    }

    public function due()
    {
        return response()->json($this->maintenanceService->checkDue());
    }

    public function generate(Request $request, MaintenanceSchedule $maintenanceSchedule)
    {
        $repairRequest = $this->maintenanceService->generateRepairRequest(
            $maintenanceSchedule,
            $request->user()->id
        );

        return $repairRequest;
    }

    public function complete(Request $request, MaintenanceSchedule $maintenanceSchedule)
    {
        $validated = $request->validate([
            'odometer' => 'required|numeric|min:0',
        ]);

        return $this->maintenanceService->completeService(
            $maintenanceSchedule->id,
            $validated['odometer']
        );
    }

    public function vehicleReport(int $vehicleId)
    {
        return response()->json(
            $this->maintenanceService->vehicleScheduleReport($vehicleId)
        );
    }

    public function costReport()
    {
        $costs = \App\Models\RepairRequest::selectRaw(
            'vehicle_id, count(*) as total_repairs, sum(select coalesce(sum(estimated_total),0) from repair_request_items where repair_request_id = repair_requests.id) as total_cost'
        )
            ->where('type', 'maintenance')
            ->groupBy('vehicle_id')
            ->with('vehicle:id,plate_number')
            ->get();

        return $costs;
    }
}
