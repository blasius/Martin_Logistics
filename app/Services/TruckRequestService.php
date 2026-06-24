<?php

namespace App\Services;

use App\Models\TruckRequest;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TruckRequestService
{
    public function generateReference(): string
    {
        $year = now()->year;
        $count = TruckRequest::whereYear('created_at', $year)->count() + 1;
        return 'TR-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function assignDispatcher(): ?User
    {
        $dispatcherRole = \Spatie\Permission\Models\Role::where('name', 'Dispatcher')->first();
        if (!$dispatcherRole) return null;

        $dispatchers = $dispatcherRole->users()->pluck('users.id');

        if ($dispatchers->isEmpty()) return null;

        $lastAssigned = TruckRequest::whereIn('dispatcher_id', $dispatchers)
            ->whereNotNull('dispatcher_id')
            ->orderBy('created_at', 'desc')
            ->value('dispatcher_id');

        if (!$lastAssigned || !$dispatchers->contains($lastAssigned)) {
            return User::find($dispatchers->first());
        }

        $ids = $dispatchers->toArray();
        $idx = array_search($lastAssigned, $ids);
        $nextIdx = ($idx + 1) % count($ids);

        return User::find($ids[$nextIdx]);
    }

    public function assignAndCreateTrip(TruckRequest $request, int $vehicleId, ?int $trailerId = null): TruckRequest
    {
        return DB::transaction(function () use ($request, $vehicleId, $trailerId) {
            $dispatcher = $this->assignDispatcher();

            $trip = Trip::create([
                'vehicle_id' => $vehicleId,
                'dispatcher_id' => $dispatcher?->id,
                'status' => 'pre_departure',
                'order_id' => $request->order_id,
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
