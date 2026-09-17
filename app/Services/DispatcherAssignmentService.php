<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Models\VehicleDispatcherAssignment;
use Illuminate\Support\Collection;

class DispatcherAssignmentService
{
    public function __construct(protected RatingService $ratingService) {}

    public function dispatcherIds(): Collection
    {
        $role = \Spatie\Permission\Models\Role::where('name', 'Dispatcher')->first();
        if (!$role) return collect();

        return $role->users()->orderBy('users.name')->pluck('users.id');
    }

    public function assignForVehicle(?int $vehicleId = null): ?User
    {
        if ($vehicleId) {
            $owner = VehicleDispatcherAssignment::where('vehicle_id', $vehicleId)
                ->latest('assigned_at')
                ->first();

            if ($owner) {
                return $owner->dispatcher;
            }
        }

        return $this->roundRobin();
    }

    public function roundRobin(): ?User
    {
        $dispatcherIds = $this->dispatcherIds();

        if ($dispatcherIds->isEmpty()) return null;

        $lastAssigned = Trip::whereIn('dispatcher_id', $dispatcherIds)
            ->whereNotNull('dispatcher_id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->value('dispatcher_id');

        if (!$lastAssigned || !$dispatcherIds->contains($lastAssigned)) {
            return User::find($dispatcherIds->first());
        }

        $ids = $dispatcherIds->toArray();
        $idx = array_search($lastAssigned, $ids);
        $nextIdx = ($idx + 1) % count($ids);

        return User::find($ids[$nextIdx]);
    }

    public function ownerOf(int $vehicleId): ?User
    {
        $ownership = VehicleDispatcherAssignment::where('vehicle_id', $vehicleId)
            ->latest('assigned_at')
            ->first();

        return $ownership?->dispatcher;
    }

    public function recordOwnership(int $vehicleId, ?int $dispatcherId): void
    {
        if (!$vehicleId || !$dispatcherId) return;

        VehicleDispatcherAssignment::where('vehicle_id', $vehicleId)->delete();

        VehicleDispatcherAssignment::create([
            'vehicle_id' => $vehicleId,
            'dispatcher_id' => $dispatcherId,
            'assigned_at' => now(),
        ]);
    }

    public function loadOverview(int $days = 30): array
    {
        $since = now()->subDays(max(1, $days))->startOfDay();
        $dispatcherIds = $this->dispatcherIds();

        $ownedBy = VehicleDispatcherAssignment::with('vehicle:id,plate_number')
            ->whereIn('dispatcher_id', $dispatcherIds)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('dispatcher_id');

        $rows = [];

        foreach ($dispatcherIds as $id) {
            $user = User::find($id);
            if (!$user) continue;

            $activeTrips = Trip::where('dispatcher_id', $id)->active()->count();

            $onTime = $this->ratingService->dispatcherOnTimeMetrics($user, $since->toDateString(), now()->toDateString());

            $owned = $ownedBy->get($id, collect());

            $rows[] = [
                'id' => $id,
                'name' => $user->name,
                'email' => $user->email,
                'active_trips' => $activeTrips,
                'trips_managed' => $onTime['trips_managed'],
                'on_time_trips' => $onTime['on_time_trips'],
                'on_time_rate' => $onTime['on_time_rate'],
                'vehicles_owned' => $owned->count(),
                'vehicles' => $owned->map(fn ($o) => [
                    'id' => $o->vehicle_id,
                    'plate_number' => $o->vehicle?->plate_number,
                ])->values(),
                'load' => $activeTrips,
            ];
        }

        return $rows;
    }
}