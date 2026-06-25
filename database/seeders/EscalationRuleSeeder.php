<?php

namespace Database\Seeders;

use App\Models\EscalationRule;
use Illuminate\Database\Seeder;

class EscalationRuleSeeder extends Seeder
{
    public function run(): void
    {
        EscalationRule::seedDefaults();
    }
}
