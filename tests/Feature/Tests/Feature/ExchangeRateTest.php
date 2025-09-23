<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_fetches_correct_rate_for_given_datetime()
    {
        $user = User::factory()->create();

        ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'RWF',
            'rate' => 1280,
            'valid_from' => Carbon::parse('2025-08-01 00:00:00'),
            'valid_to' => Carbon::parse('2025-08-31 23:59:59'),
            'created_by' => $user->id,
        ]);

        ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'RWF',
            'rate' => 1295,
            'valid_from' => Carbon::parse('2025-09-01 00:00:00'),
            'valid_to' => null,
            'created_by' => $user->id,
        ]);

        $rate = ExchangeRate::validAt('USD', 'RWF', Carbon::parse('2025-08-20'))->first();
        $this->assertEquals(1280, $rate->rate);

        $rate = ExchangeRate::validAt('USD', 'RWF', Carbon::parse('2025-09-15'))->first();
        $this->assertEquals(1295, $rate->rate);
    }
}
