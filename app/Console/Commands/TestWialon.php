<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WialonService;

class TestWialon extends Command
{
    protected $signature = 'wialon:test-connection';
    protected $description = 'Test connection with Wialon API';

    public function handle(WialonService $wialon)
    {
        try {
            $result = $wialon->test();

            if (isset($result['eid'])) {
                $this->info("✅ Connected to Wialon, session id: " . $result['eid']);
            } else {
                $this->error("❌ Failed: " . json_encode($result));
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        return 0;
    }
}
