<?php

namespace App\Services;

use App\Models\TripPreparation;
use App\Models\Trip;

class DispatchPreparationService
{
    public function __construct(protected TripStateMachineService $tripStateMachine) {}

    public function getOrCreate(int $tripId): TripPreparation
    {
        return TripPreparation::firstOrCreate(
            ['trip_id' => $tripId],
            ['prepared_by' => auth()->id()]
        );
    }

    public function update(int $tripId, array $data): TripPreparation
    {
        $prep = $this->getOrCreate($tripId);
        $prep->update($data);
        return $prep->fresh();
    }

    public function markReady(int $tripId): TripPreparation
    {
        $prep = $this->getOrCreate($tripId);

        if (!$prep->is_complete) {
            throw new \RuntimeException('Cannot mark trip ready: checklist incomplete');
        }

        $prep->update([
            'ready_at' => now(),
            'prepared_by' => auth()->id(),
        ]);

        try {
            $this->tripStateMachine->transitionByCode($prep->trip, 'mark_ready', [
                'actor' => auth()->user(),
                'trigger' => 'dispatcher',
            ]);
        } catch (\App\Exceptions\TripTransitionNotAllowedException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $prep->fresh();
    }

    public function needsPreparation(): array
    {
        $stateKey = $this->tripStateMachine->configuredStateKey('trip_flow.needs_preparation_state', 'pre_departure');

        return Trip::with(['vehicle', 'preparation', 'dispatcher'])
            ->where('status', $stateKey)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function readyToDepart(): array
    {
        $stateKey = $this->tripStateMachine->configuredStateKey('trip_flow.ready_to_depart_state', 'assigned');

        return Trip::with(['vehicle', 'preparation', 'dispatcher'])
            ->where('status', $stateKey)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
