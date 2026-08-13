<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Trip;
use App\Models\TripHistory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_get_current_active_trip()
    {
        $user = User::factory()->create();
        $driver = Driver::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'order_id' => $order->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'assigned',
        ]);

        VehicleSnapshot::create([
            'vehicle_id' => $vehicle->id,
            'last_seen_at' => now(),
            'latitude' => -1.9501,
            'longitude' => 30.0619,
            'speed' => 45.5,
            'fuel_level' => 62.0,
            'ignition' => true,
            'is_moving' => true,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/mobile/trips/current');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'driver' => ['id', 'user_id', 'name', 'email', 'phone', 'whatsapp_phone'],
                     'vehicle' => ['id', 'plate_number', 'trailer'],
                     'position' => ['latitude', 'longitude', 'fuel_level', 'last_seen_at'],
                     'assigned_staff',
                     'trip' => [
                         'id',
                         'status',
                         'order' => ['id', 'reference', 'origin', 'destination'],
                         'vehicle' => ['id', 'plate_number']
                     ]
                 ])
                 ->assertJsonPath('trip.id', $trip->id)
                 ->assertJsonPath('driver.name', $user->name)
                 ->assertJsonPath('vehicle.plate_number', $vehicle->plate_number)
                 ->assertJsonPath('position.fuel_level', 62.0);
    }

    public function test_driver_can_get_profile()
    {
        $user = User::factory()->create();
        $driver = Driver::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create();
        $vehicle = Vehicle::factory()->create();

        Trip::factory()->create([
            'driver_id' => $driver->id,
            'order_id' => $order->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'delivered',
            'actual_distance_km' => 1200.5,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/mobile/profile');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'driver' => ['id', 'user_id', 'name', 'email', 'phone', 'whatsapp_phone', 'rating', 'rating_count', 'member_since'],
                     'vehicle' => ['id', 'plate_number', 'trailer'],
                     'stats' => ['total_trips', 'completed_trips', 'pending_trips', 'total_distance_km', 'hours_driven'],
                     'latest_trips' => [[
                         'id',
                         'reference',
                         'status',
                         'origin',
                         'destination',
                         'ended_at',
                     ]],
                 ])
                 ->assertJsonPath('driver.name', $user->name)
                 ->assertJsonPath('stats.total_trips', 1)
                 ->assertJsonPath('stats.completed_trips', 1)
                 ->assertJsonPath('stats.total_distance_km', 1200.5);
    }

    public function test_user_without_driver_profile_cannot_get_trip()
    {
        $user = User::factory()->create(); // No driver profile attached

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/mobile/trips/current');

        $response->assertStatus(403)
                 ->assertJson(['message' => 'User is not registered as a driver.']);
    }

    public function test_driver_can_update_trip_status()
    {
        $user = User::factory()->create();
        $driver = Driver::factory()->create(['user_id' => $user->id]);

        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/mobile/trips/{$trip->id}/status", [
            'status' => 'on_route',
            'notes' => 'Leaving the depot.'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Trip status updated successfully.', 'status' => 'on_route']);

        $this->assertEquals('on_route', $trip->fresh()->status);

        $this->assertDatabaseHas('trip_histories', [
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'action' => 'status_update',
        ]);

        $history = TripHistory::where('trip_id', $trip->id)->first();
        $this->assertEquals('assigned', $history->changes['old_status']);
        $this->assertEquals('on_route', $history->changes['new_status']);
        $this->assertEquals('Leaving the depot.', $history->changes['notes']);
    }

    public function test_driver_cannot_update_another_drivers_trip()
    {
        $user1 = User::factory()->create();
        $driver1 = Driver::factory()->create(['user_id' => $user1->id]);

        $user2 = User::factory()->create();
        $driver2 = Driver::factory()->create(['user_id' => $user2->id]);

        $trip = Trip::factory()->create([
            'driver_id' => $driver2->id, // Trip belongs to driver 2
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($user1, ['*']); // Logging in as driver 1

        $response = $this->postJson("/api/mobile/trips/{$trip->id}/status", [
            'status' => 'on_route',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized access to this trip.']);
    }
}
