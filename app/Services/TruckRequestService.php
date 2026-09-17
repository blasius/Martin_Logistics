<?php

namespace App\Services;

use App\Models\TruckRequest;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TruckRequestService
{
    public function __construct(
        protected TripStateMachineService $tripStateMachine,
        protected DispatcherAssignmentService $dispatcherAssignment,
    ) {}

    public function generateReference(): string
    {
        $year = now()->year;
        $count = TruckRequest::whereYear('created_at', $year)->count() + 1;
        return 'TR-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function assignDispatcher(): ?User
    {
        return $this->dispatcherAssignment->roundRobin();
    }

    public function assignAndCreateTrip(TruckRequest $request, int $vehicleId, ?int $trailerId = null): TruckRequest
    {
        return DB::transaction(function () use ($request, $vehicleId, $trailerId) {
            $dispatcher = $this->dispatcherAssignment->assignForVehicle($vehicleId);

            $initialStatus = $this->tripStateMachine->initialState()?->key ?? 'pre_departure';

            $trip = Trip::create([
                'vehicle_id' => $vehicleId,
                'dispatcher_id' => $dispatcher?->id,
                'status' => $initialStatus,
                'order_id' => $request->order_id,
            ]);

            $this->dispatcherAssignment->recordOwnership($vehicleId, $dispatcher?->id);

            $this->tripStateMachine->recordStatus($trip, $initialStatus, [
                'action' => 'truck_request_trip_created',
                'notes' => 'Trip created from truck request',
            ]);

            $request->update([
                'assigned_vehicle_id' => $vehicleId,
                'assigned_trailer_id' => $trailerId,
                'dispatcher_id' => $dispatcher?->id,
                'trip_id' => $trip->id,
                'status' => 'truck_assigned',
            ]);

            TripPreparation::create([
                'trip_id' => $trip->id,
            ]);

            return $request->fresh();
        });
    }

    public function getAvailableVehicles(TruckRequest $request): array
    {
        $vehicles = \App\Models\Vehicle::where('status', 'active')
            ->where('is_operational', true)
            ->whereDoesntHave('activeTrip')
            ->get();

        $filtered = $vehicles->filter(function ($v) use ($request) {
            // Capacity check
            $capacity = $v->capacity_kg ?? $v->max_weight ?? 0;
            if ($capacity > 0 && $capacity < $request->tonnage * 1000) {
                return false;
            }

            return true;
        });

        return $filtered->values()->toArray();
    }
}
