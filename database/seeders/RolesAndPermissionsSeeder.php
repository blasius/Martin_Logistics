<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear old permissions and roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the roles
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $adminRole      = Role::create(['name' => 'admin']);
        $operatorRole   = Role::create(['name' => 'operator']);
        $driverRole     = Role::create(['name' => 'driver']);
        $customerRole   = Role::create(['name' => 'customer']);

        // Define a permission, for example, to view the dashboard
        Permission::create(['name' => 'view dashboard']);

        // Assign the permission to the roles that need it
        $superAdminRole->givePermissionTo('view dashboard');
        $adminRole->givePermissionTo('view dashboard');
        $operatorRole->givePermissionTo('view dashboard');
    }
}
