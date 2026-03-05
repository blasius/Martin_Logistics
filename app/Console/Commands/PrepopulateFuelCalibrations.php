<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\WialonService; // Ensure this matches your service path

class PrepopulateFuelCalibrations extends Command
{
    protected $signature = 'fuel:prepopulate';
    protected $description = 'Fetch and store all vehicle fuel calibrations from Wialon';

    public function handle(WialonService $wialon)
    {
        $this->info("Starting calibration fetch...");

        // Access the tokens property directly from your service
        // Ensure 'tokens' is public or has a getter in WialonService
        $tokens = $wialon->tokens;

        if (empty($tokens)) {
            $this->error("No tokens found in WialonService. Check your .env or Service initialization.");
            return;
        }

        foreach ($tokens as $fleetName => $token) {
            $this->info("Fetching calibrations for fleet: " . (is_string($fleetName) ? $fleetName : 'Account'));

            $result = $wialon->callApi('core/search_items', [
                'spec' => [
                    'itemsType' => 'avl_unit',
                    'propName' => 'sys_name',
                    'propValueMask' => '*',
                    'sortType' => 'sys_name',
                ],
                'force' => 1,
                'flags' => 4097, // Base + Sensors
                'from' => 0,
                'to' => 0,
            ], $token);

            if (!isset($result['items'])) {
                $this->warn("No items found for this token.");
                continue;
            }

            $bar = $this->output->createProgressBar(count($result['items']));
            $count = 0;

            foreach ($result['items'] as $item) {
                // Find our local vehicle by Wialon ID
                $vehicle = DB::table('wialon_units')
                    ->where('wialon_id', $item['id'])
                    ->first();

                if (!$vehicle || !$vehicle->vehicle_id) {
                    $bar->advance();
                    continue;
                }

                // Find the fuel sensor
                $fuelSensor = collect($item['sens'] ?? [])->firstWhere('t', 'fuel level');

                if ($fuelSensor) {
                    DB::table('fuel_calibrations')->updateOrInsert(
                        ['vehicle_id' => $vehicle->vehicle_id],
                        [
                            'calibration_table' => json_encode($fuelSensor['tbl']),
                            'last_wialon_mt' => $fuelSensor['mt'] ?? 0,
                            'updated_at' => now(),
                        ]
                    );
                    $count++;
                }
                $bar->advance();
            }

            $bar->finish();
            $this->info("\nFinished. Populated $count calibrations for this fleet.");
        }

        $this->info("\nAll processing complete.");
    }
}
