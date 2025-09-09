<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_filament_dashboard()
    {
        Role::create(['name' => 'Admin']);
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(200);
    }

    public function test_operator_cannot_access_filament_dashboard()
    {
        Role::create(['name' => 'Operator']);
        $user = User::factory()->create();
        $user->assignRole('Operator');

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);
    }
}
