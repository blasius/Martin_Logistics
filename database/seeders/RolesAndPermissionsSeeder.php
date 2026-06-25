<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Trips
            ['name' => 'view_assigned_trips', 'group' => 'trips', 'description' => 'View trips assigned to self'],
            ['name' => 'view_all_trips', 'group' => 'trips', 'description' => 'View all trips in the system'],
            ['name' => 'create_trips', 'group' => 'trips', 'description' => 'Create new trips'],
            ['name' => 'assign_truck', 'group' => 'trips', 'description' => 'Assign trucks to trips'],
            ['name' => 'assign_dispatcher', 'group' => 'trips', 'description' => 'Assign dispatchers to trips'],
            ['name' => 'cancel_trips', 'group' => 'trips', 'description' => 'Cancel trips'],
            ['name' => 'approve_deviation', 'group' => 'trips', 'description' => 'Approve route deviations'],

            // Expenses
            ['name' => 'view_own_expenses', 'group' => 'expenses', 'description' => 'View own expense submissions'],
            ['name' => 'view_all_expenses', 'group' => 'expenses', 'description' => 'View all expense records'],
            ['name' => 'create_expense', 'group' => 'expenses', 'description' => 'Create expense records'],
            ['name' => 'approve_stage1_expense', 'group' => 'expenses', 'description' => 'Stage 1 expense approval'],
            ['name' => 'approve_stage2_expense', 'group' => 'expenses', 'description' => 'Stage 2 expense approval'],
            ['name' => 'manage_expense_catalog', 'group' => 'expenses', 'description' => 'Manage expense type catalog'],
            ['name' => 'pay_expenses', 'group' => 'expenses', 'description' => 'Process expense payments'],

            // Vehicles
            ['name' => 'view_vehicles', 'group' => 'vehicles', 'description' => 'View vehicle records'],
            ['name' => 'edit_vehicles', 'group' => 'vehicles', 'description' => 'Edit vehicle details'],
            ['name' => 'transfer_vehicles', 'group' => 'vehicles', 'description' => 'Transfer vehicles'],
            ['name' => 'deactivate_vehicles', 'group' => 'vehicles', 'description' => 'Deactivate vehicles'],

            // Users
            ['name' => 'view_users', 'group' => 'users', 'description' => 'View user accounts'],
            ['name' => 'create_users', 'group' => 'users', 'description' => 'Create new user accounts'],
            ['name' => 'assign_role', 'group' => 'users', 'description' => 'Assign roles to users'],
            ['name' => 'manage_permissions', 'group' => 'users', 'description' => 'Manage roles and permissions'],

            // Workshop
            ['name' => 'view_queue', 'group' => 'workshop', 'description' => 'View workshop queue'],
            ['name' => 'assign_mechanic', 'group' => 'workshop', 'description' => 'Assign mechanics to repairs'],
            ['name' => 'approve_repair', 'group' => 'workshop', 'description' => 'Approve repair requests'],
            ['name' => 'release_vehicle', 'group' => 'workshop', 'description' => 'Release vehicle from workshop'],

            // Fuel
            ['name' => 'dispense_fuel', 'group' => 'fuel', 'description' => 'Dispense fuel to vehicles'],
            ['name' => 'view_fuel_reports', 'group' => 'fuel', 'description' => 'View fuel consumption reports'],
            ['name' => 'manage_tanks', 'group' => 'fuel', 'description' => 'Manage fuel tank inventory'],
            ['name' => 'approve_supplier_delivery', 'group' => 'fuel', 'description' => 'Approve fuel supplier deliveries'],

            // Finance
            ['name' => 'view_pending_payments', 'group' => 'finance', 'description' => 'View pending payment requests'],
            ['name' => 'process_payment', 'group' => 'finance', 'description' => 'Process payments'],
            ['name' => 'view_financial_reports', 'group' => 'finance', 'description' => 'View financial reports'],
            ['name' => 'reconcile', 'group' => 'finance', 'description' => 'Perform account reconciliation'],
            ['name' => 'manage_invoices', 'group' => 'finance', 'description' => 'Create and manage invoices'],
            ['name' => 'manage_wallets', 'group' => 'finance', 'description' => 'Manage user wallets'],

            // Containers
            ['name' => 'view_containers', 'group' => 'containers', 'description' => 'View container records'],
            ['name' => 'track_containers', 'group' => 'containers', 'description' => 'Track container movements'],
            ['name' => 'impute_demurrage', 'group' => 'containers', 'description' => 'Calculate demurrage charges'],

            // Reports
            ['name' => 'view_operational_reports', 'group' => 'reports', 'description' => 'View operational reports'],
            ['name' => 'view_financial_reports', 'group' => 'reports', 'description' => 'View financial reports'],
            ['name' => 'view_driver_performance', 'group' => 'reports', 'description' => 'View driver performance data'],
            ['name' => 'export_reports', 'group' => 'reports', 'description' => 'Export reports to CSV/PDF'],

            // General
            ['name' => 'manage_orders', 'group' => 'general', 'description' => 'Create and manage customer orders'],
            ['name' => 'manage_clients', 'group' => 'general', 'description' => 'Manage client records'],
            ['name' => 'view_dashboard', 'group' => 'general', 'description' => 'View main dashboard'],
            ['name' => 'manage_contracts', 'group' => 'general', 'description' => 'Manage customer contracts'],
            ['name' => 'manage_rate_cards', 'group' => 'general', 'description' => 'Manage rate cards and pricing'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                [
                    'slug' => Str::slug($perm['name']),
                    'group' => $perm['group'],
                    'description' => $perm['description'],
                ]
            );
        }

        $roleConfig = [
            'Super Admin' => [
                'is_super_admin' => true,
                'description' => 'Full access to everything in the system',
                'permissions' => Permission::all()->pluck('name')->toArray(),
            ],
            'Director of Operations' => [
                'is_super_admin' => false,
                'description' => 'Approve Stage 2, view all, escalate to MD',
                'permissions' => [
                    'view_all_trips', 'cancel_trips', 'approve_deviation',
                    'view_all_expenses', 'approve_stage2_expense',
                    'view_vehicles', 'edit_vehicles', 'transfer_vehicles',
                    'view_queue', 'approve_repair', 'release_vehicle',
                    'view_fuel_reports', 'approve_supplier_delivery',
                    'view_pending_payments', 'process_payment', 'view_financial_reports',
                    'view_operational_reports', 'view_financial_reports',
                    'view_driver_performance', 'export_reports',
                    'manage_orders', 'manage_clients', 'view_dashboard',
                ],
            ],
            'Logistics Manager' => [
                'is_super_admin' => false,
                'description' => 'Assign trucks, approve Stage 1, manage expense catalog',
                'permissions' => [
                    'view_assigned_trips', 'view_all_trips', 'create_trips', 'assign_truck', 'assign_dispatcher',
                    'view_own_expenses', 'view_all_expenses', 'create_expense', 'approve_stage1_expense', 'manage_expense_catalog',
                    'view_vehicles', 'edit_vehicles',
                    'view_queue', 'assign_mechanic', 'approve_repair',
                    'view_fuel_reports',
                    'view_operational_reports', 'view_driver_performance', 'export_reports',
                    'manage_orders', 'manage_contracts', 'manage_rate_cards', 'view_dashboard',
                ],
            ],
            'Operations Manager' => [
                'is_super_admin' => false,
                'description' => 'Review deviations, reassign trips',
                'permissions' => [
                    'view_all_trips', 'assign_truck', 'approve_deviation',
                    'view_all_expenses', 'approve_stage1_expense',
                    'view_vehicles',
                    'view_queue',
                    'view_fuel_reports',
                    'view_operational_reports', 'view_driver_performance',
                    'view_dashboard',
                ],
            ],
            'Dispatcher' => [
                'is_super_admin' => false,
                'description' => 'View assigned trips, create tickets, convert to expense',
                'permissions' => [
                    'view_assigned_trips', 'create_trips',
                    'view_own_expenses', 'create_expense',
                    'view_vehicles',
                    'view_dashboard',
                ],
            ],
            'Finance Officer' => [
                'is_super_admin' => false,
                'description' => 'View pending payments, process payments, view financial reports',
                'permissions' => [
                    'view_pending_payments', 'process_payment', 'view_financial_reports', 'reconcile',
                    'manage_invoices', 'manage_wallets',
                    'view_all_expenses',
                    'view_operational_reports', 'export_reports',
                    'view_dashboard',
                ],
            ],
            'Driver' => [
                'is_super_admin' => false,
                'description' => 'Mobile app: view trips, submit tickets, view wallet, acknowledge',
                'permissions' => [
                    'view_assigned_trips',
                    'view_own_expenses', 'create_expense',
                ],
            ],
            'Admin' => [
                'is_super_admin' => false,
                'description' => 'System administrator with broad access',
                'permissions' => Permission::all()->pluck('name')->toArray(),
            ],
        ];

        foreach ($roleConfig as $roleName => $config) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                [
                    'slug' => Str::slug($roleName),
                    'description' => $config['description'],
                    'is_super_admin' => $config['is_super_admin'],
                    'is_active' => true,
                ]
            );

            $role->syncPermissions($config['permissions']);
        }
    }
}
