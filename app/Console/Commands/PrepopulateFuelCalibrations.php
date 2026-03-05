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

        // We use Flag 4097 (Base + Sensors) to get the 'sens' block
        $result = $wialon->callApi('core/search_items', [
            'spec' => [
                'itemsType' => 'avl_unit',
                'propName' => 'sys_name',
                'propValueMask' => '*',
                'sortType' => 'sys_name',
            ],
            'force' => 1,
            'flags' => 4097,
            'from' => 0,
            'to' => 0,
        ]);

        if (!isset($result['items'])) {
            $this->error("Failed to fetch units from Wialon.");
            return;
        }

        $bar = $this->output->createProgressBar(count($result['items']));
        $count = 0;

        foreach ($result['items'] as $item) {
            $wialonId = $item['id'];

            // Link to your local vehicle record
            $vehicle = DB::table('wialon_units')->where('wialon_id', $wialonId)->first();

            if (!$vehicle || !$vehicle->vehicle_id) {
                $bar->advance();
                continue;
            }

            // Find the fuel level sensor in the 'sens' object
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
        $this->info("\nDone! Populated $count vehicle calibrations.");
    }
}
