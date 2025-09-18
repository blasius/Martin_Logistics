<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class WialonSyncSeeder extends Seeder
{
    public function run()
    {
        // You can pass --create-if-empty to seed vehicles when empty
        Artisan::call('wialon:sync-vehicles', ['--create-if-empty' => true]);
        $this->command->info('Wialon units synced via Seeder.');
    }
}
