<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WialonService;

class TestWialon extends Command
{
    protected $signature = 'wialon:test-connection';
    protected $description = 'Test connection with Wialon API across all fleets';

    public function handle(WialonService $wialon)
    {
        $this->info("Initiating Multi-Fleet Connection Test...");
        $this->newLine();

        try {
            $results = $wialon->test();

            foreach ($results as $fleetKey => $data) {
                if (isset($data['status']) && str_contains($data['status'], 'Success')) {
                    $this->info("[$fleetKey] ✅ Connected: {$data['user']}");
                    $this->line("   Session ID: {$data['eid']}");
                } else {
                    $this->error("[$fleetKey] ❌ Failed");
                    $this->line("   Details: " . json_encode($data));
                }
                $this->newLine();
            }

            // Let's also test pulling the actual unit count
            $this->info("Testing combined unit retrieval...");
            $units = $wialon->getUnitsOnly();
            $this->info("✅ Total units found across all fleets: " . count($units));

        } catch (\Exception $e) {
            $this->error("❌ Critical Error: " . $e->getMessage());
        }

        return 0;
    }
}
