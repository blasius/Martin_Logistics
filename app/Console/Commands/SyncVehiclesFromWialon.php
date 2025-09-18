<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WialonService;
use App\Services\WialonMatcher;
use App\Models\WialonUnit;
use App\Models\Vehicle;

class SyncVehiclesFromWialon extends Command
{
    protected $signature = 'wialon:sync-vehicles';
    protected $description = 'Two-way sync between Wialon units and Vehicles';

    public function handle(WialonService $wialon)
    {
        $this->info("Fetching units from Wialon...");
        $units = $wialon->getUnits();

        if (!isset($units['items'])) {
            $this->error("API error: " . json_encode($units));
            return 1;
        }

        $this->info("Units received: " . count($units['items']));

        foreach ($units['items'] as $unit) {
            $wialonId = (string) ($unit['id'] ?? '');
            $name     = $unit['nm'] ?? null;
            $uid      = $unit['uid'] ?? null;
            $hw       = $unit['hw'] ?? null;

            if (!$wialonId) continue;

            $wialonUnit = WialonUnit::updateOrCreate(
                ['wialon_id' => $wialonId],
                ['name' => $name, 'uid' => $uid, 'device_type' => $hw]
            );

            $plate = WialonMatcher::extractPlate($name);
            $matchedVehicle = null;

            if ($plate) {
                $matchedVehicle = Vehicle::whereRaw('UPPER(REPLACE(plate_number, " ", "")) = ?', [$plate])->first();

                if (!$matchedVehicle) {
                    // Create missing vehicle
                    $matchedVehicle = Vehicle::create([
                        'plate_number' => $plate,
                        'name' => $name,
                    ]);
                    $this->info("Created vehicle {$plate} from Wialon");
                }
            }

            if ($matchedVehicle) {
                $wialonUnit->vehicle()->associate($matchedVehicle);
                $wialonUnit->is_linked = true;
                $wialonUnit->save();
                $this->info("Linked unit {$name} -> vehicle {$matchedVehicle->plate_number}");
            }
        }

        // Now check vehicles without units
        $vehicles = Vehicle::doesntHave('wialonUnit')->get();
        foreach ($vehicles as $v) {
            $this->warn("Vehicle {$v->plate_number} has no Wialon unit linked.");
        }

        $this->info("Two-way sync complete.");
        return 0;
    }
}
