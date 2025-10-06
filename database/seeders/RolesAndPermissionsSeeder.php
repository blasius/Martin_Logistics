<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage_users', 'manage_orders', 'manage_trips', 'manage_vehicles',
            'view_reports', 'approve_trip', 'view_own_trips', 'view_own_orders'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Create roles and assign permissions
        $roles = [
            'Super Admin' => Permission::all(),
            'Admin' => ['manage_users', 'manage_orders', 'manage_trips', 'manage_vehicles', 'view_reports'],
            'Operator' => ['manage_orders', 'manage_trips'],
            'Driver' => ['view_own_trips'],
            'Customer' => ['view_own_orders'],
        ];

        foreach ($roles as $role => $perms) {
            $r = Role::firstOrCreate(['name' => $role]);
            if ($perms instanceof \Illuminate\Support\Collection) {
                $r->syncPermissions($perms);
            } else {
                $r->syncPermissions($perms);
            }
        }
    }
}
