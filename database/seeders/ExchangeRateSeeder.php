<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $rwf = Currency::where('code', 'RWF')->first();
        $usd = Currency::where('code', 'USD')->first();

        if (!$rwf || !$usd) {
            $this->command->warn('Currencies not found. Skipping ExchangeRateSeeder.');
            return;
        }

        ExchangeRate::create([
            'base_currency_id' => $usd->id,
            'target_currency_id' => $rwf->id,
            'base_currency' => 'USD',
            'target_currency' => 'RWF',
            'rate' => 1460,
            'valid_from' => now()->startOfDay(),
            'created_by' => null,
        ]);

        $this->command->info('Seeded USD → RWF exchange rate at 1460.');
    }
}
