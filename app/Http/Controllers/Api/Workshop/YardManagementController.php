<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\DockDoor;
use App\Models\ServiceQueue;
use App\Models\YardEntry;
use App\Services\YardService;
use Illuminate\Http\Request;

class YardManagementController extends Controller
{
    public function __construct(protected YardService $yardService) {}

    // Dashboard

    public function dashboard()
    {
        return response()->json($this->yardService->dashboardStats());
    }

    // Dock Doors CRUD

    public function dockDoors()
    {
        return DockDoor::with(['currentVehicle:id,plate_number', 'warehouse:id,name'])
            ->orderBy('service_type')
            ->orderBy('code')
            ->get();
    }

    public function storeDockDoor(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:dock_doors,code',
            'name' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'service_type' => 'required|in:loading_dock,unload_dock,fueling_bay,car_wash,workshop',
            'notes' => 'nullable|string',
        ]);

        $door = DockDoor::create($validated);

        return response()->json($door, 201);
    }

    public function updateDockDoor(Request $request, DockDoor $dockDoor)
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:255|unique:dock_doors,code,' . $dockDoor->id,
            'name' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'service_type' => 'sometimes|in:loading_dock,unload_dock,fueling_bay,car_wash,workshop',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $dockDoor->update($validated);

        return response()->json($dockDoor);
    }

    public function destroyDockDoor(DockDoor $dockDoor)
    {
        abort_if($dockDoor->is_occupied, 422, 'Cannot delete an occupied dock door.');
        $dockDoor->delete();

        return response()->json(['message' => 'Dock door deleted']);
    }

    // Station Assignment

    public function assignDockDoor(Request $request)
    {
        $validated = $request->validate([
            'queue_id' => 'required|exists:service_queues,id',
            'dock_door_id' => 'required|exists:dock_doors,id',
        ]);

        $entry = $this->yardService->assignDockDoor($validated['queue_id'], $validated['dock_door_id']);

        return response()->json(['message' => 'Dock door assigned', 'entry' => $entry]);
    }

    public function releaseDockDoor(DockDoor $dockDoor)
    {
        $this->yardService->releaseDockDoor($dockDoor->id);

        $next = $this->yardService->autoAssignNext($dockDoor->service_type);

        return response()->json([
            'message' => 'Dock door released',
            'next_assigned' => $next,
        ]);
    }

    // Yard Entries

    public function yardEntries(Request $request)
    {
        $query = YardEntry::with(['vehicle:id,plate_number,make,model', 'driver:id', 'dockDoor:id,code']);

        if ($request->active) {
            $query->whereNull('check_out_at');
        }

        return $query->orderByDesc('check_in_at')->paginate($request->per_page ?? 20);
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'purpose' => 'required|in:loading,unloading,fueling,washing,workshop,parking',
            'driver_id' => 'nullable|exists:drivers,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $coordinates = null;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $coordinates = ['lat' => (float) $validated['latitude'], 'lng' => (float) $validated['longitude']];
        }

        $entry = $this->yardService->checkIn(
            $validated['vehicle_id'],
            $validated['purpose'],
            $validated['driver_id'] ?? null,
            $request->user()->id,
            $coordinates
        );

        return response()->json($entry, 201);
    }

    public function checkOut(YardEntry $yardEntry)
    {
        $entry = $this->yardService->checkOut($yardEntry->id);

        return response()->json(['message' => 'Checked out', 'entry' => $entry]);
    }

    // Queue detail per service type

    public function queueByType(string $serviceType, Request $request)
    {
        $validTypes = ['offload', 'wash', 'workshop', 'fuel', 'loading_dock', 'unload_dock', 'fueling_bay', 'car_wash'];
        abort_unless(in_array($serviceType, $validTypes), 404, 'Invalid service type.');

        $queue = $this->yardService->queue($serviceType, $request->status);

        $waitTime = $this->yardService->estimatedWaitTime($serviceType);

        return response()->json([
            'queue' => $queue,
            'wait_time' => $waitTime,
        ]);
    }

    // Wait time

    public function waitTime(string $serviceType)
    {
        return response()->json($this->yardService->estimatedWaitTime($serviceType));
    }
}
