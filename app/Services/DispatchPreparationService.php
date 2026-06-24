<?php

namespace App\Services;

use App\Models\TripPreparation;
use App\Models\Trip;

class DispatchPreparationService
{
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

        $prep->trip->update(['status' => 'assigned']);

        return $prep->fresh();
    }

    public function needsPreparation(): array
    {
        return Trip::with(['vehicle', 'preparation', 'dispatcher'])
            ->where('status', 'pre_departure')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function readyToDepart(): array
    {
        return Trip::with(['vehicle', 'preparation', 'dispatcher'])
            ->where('status', 'assigned')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
