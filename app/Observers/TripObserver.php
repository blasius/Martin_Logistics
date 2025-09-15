<?php

namespace App\Observers;

use App\Models\Trip;

class TripObserver
{
    public function creating(Trip $trip)
    {
        // Vehicle snapshot
        if ($trip->vehicle) {
            $trip->vehicle_plate_snapshot = $trip->vehicle->plate_number;

            // Grab the latest *currently attached* trailer
            $latestTrailer = $trip->vehicle?->latestTrailer()?->first();
            if ($latestTrailer) {
                $trip->trailer_plate_snapshot = $latestTrailer->plate_number;
            }
        }

        // Driver snapshot
        if ($trip->driver && $trip->driver->user) {
            $trip->driver_name_snapshot = $trip->driver->user->name;
        }

        // Track creator if not already set
        if (auth()->check() && !$trip->created_by) {
            $trip->created_by = auth()->id();
        }
    }
}
