<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WialonService;
use App\Services\WialonMatcher;
use App\Models\WialonUnit;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

class SyncVehiclesFromWialon extends Command
{
    protected $signature = 'wialon:sync-vehicles';
    protected $description = 'Two-way sync between Wialon units and Vehicles for all fleets';

    public function handle(WialonService $wialon)
    {
        $this->info("Fetching units from ALL Wialon accounts...");

        // Note: Ensure your Service method is named 'getUnitsOnly' or 'getUnits'
        // and returns the combined array of items.
        $units = $wialon->getUnitsOnly();

        if (empty($units)) {
            $this->error("No units found or API connection failed for all fleets.");
            return 1;
        }

        $this->info("Total units received across all fleets: " . count($units));

        foreach ($units as $unit) {
            $wialonId = (string) ($unit['id'] ?? '');
            $name     = $unit['nm'] ?? 'Unknown';
            $uid      = $unit['uid'] ?? null;
            $hw       = $unit['hw'] ?? null;

            if (!$wialonId) continue;

            // 1. Update or create the WialonUnit (The bridge record)
            $wialonUnit = WialonUnit::updateOrCreate(
                ['wialon_id' => $wialonId],
                [
                    'name' => $name,
                    'uid' => $uid,
                    'device_type' => $hw
                ]
            );

            // 2. Attempt to find/create a Vehicle to link to
            $plate = WialonMatcher::extractPlate($name);

            if ($plate) {
                // Use a normalized search to find existing vehicle
                $normalizedPlate = strtoupper(str_replace(' ', '', $plate));

                $matchedVehicle = Vehicle::whereRaw('UPPER(REPLACE(plate_number, " ", "")) = ?', [$normalizedPlate])->first();

                if (!$matchedVehicle) {
                    $matchedVehicle = Vehicle::create([
                        'plate_number' => $plate,
                        'name' => $name,
                    ]);
                    $this->info("Created NEW vehicle: {$plate}");
                }

                // 3. Link them
                $wialonUnit->vehicle_id = $matchedVehicle->id;
                $wialonUnit->is_linked = true;
                $wialonUnit->save();

                $this->line("   Linked: {$name} -> [Vehicle ID: {$matchedVehicle->id}]");
            } else {
                $this->warn("   Skipped linking for: {$name} (No plate detected)");
            }
        }

        // 4. Final Audit
        $unlinkedCount = Vehicle::doesntHave('wialonUnit')->count();
        if ($unlinkedCount > 0) {
            $this->warn("Note: {$unlinkedCount} vehicles in your database are still not linked to a Wialon unit.");
        }

        $this->info("Two-way sync complete for all accounts.");
        return 0;
    }
}
