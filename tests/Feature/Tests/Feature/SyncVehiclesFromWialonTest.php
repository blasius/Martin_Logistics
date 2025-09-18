<?php

namespace Tests\Feature;

use App\Console\Commands\SyncVehiclesFromWialon;
use App\Models\Vehicle;
use App\Models\WialonUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncVehiclesFromWialonTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_vehicle_if_none_exists_and_links_to_wialon_unit()
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    ['id' => '12345', 'nm' => 'HOWO RAG 898Q'],
                ],
            ], 200),
        ]);

        $this->artisan('sync:wialon-vehicles')
            ->expectsOutput('Sync completed successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('vehicles', [
            'plate_number' => 'RAG898Q',
        ]);

        $this->assertDatabaseHas('wialon_units', [
            'wialon_id' => '12345',
            'plate_number' => 'RAG898Q',
        ]);

        $unit = WialonUnit::first();
        $this->assertNotNull($unit->vehicle_id);
    }

    /** @test */
    public function it_links_to_existing_vehicle_if_plate_matches()
    {
        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'RAG898Q',
        ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    ['id' => '12345', 'nm' => 'HOWO RAG 898Q'],
                ],
            ], 200),
        ]);

        $this->artisan('sync:wialon-vehicles');

        $unit = WialonUnit::first();
        $this->assertEquals($vehicle->id, $unit->vehicle_id);
    }

    /** @test */
    public function extract_plate_number_regex_works_correctly()
    {
        $cmd = new SyncVehiclesFromWialon();

        $method = new \ReflectionMethod($cmd, 'extractPlateNumber');
        $method->setAccessible(true);

        $this->assertEquals('RAG898Q', $method->invoke($cmd, 'HOWO RAG 898Q'));
        $this->assertEquals('RAH468E', $method->invoke($cmd, 'HOWO RAH 468E'));
        $this->assertNull($method->invoke($cmd, 'InvalidName'));
    }
}
