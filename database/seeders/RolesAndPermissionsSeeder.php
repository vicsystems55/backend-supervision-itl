<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache to avoid duplicate errors
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ---- Define permissions ----
        $permissions = [
            // Users & Roles
            'create users', 'edit users', 'delete users', 'view users',
            'create roles', 'edit roles', 'delete roles', 'view roles',

            // Shipments
            'create shipments', 'update shipments', 'delete shipments', 'view shipments',

            // Trucks
            'assign trucks', 'update truck location', 'view trucks',

            // Warehouses
            'manage warehouses', 'view warehouses',

            // Installations
            'create installations', 'update installations', 'approve installations', 'view installations',

            // Deliveries
            'create deliveries', 'update deliveries', 'approve deliveries', 'view deliveries',

            // Reports
            'create reports', 'approve reports', 'view reports',

            // Notifications
            'view notifications', 'manage notifications',

            // Activity Logs
            'view activity logs',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ---- Define roles and assign permissions ----
        $roles = [
            'Super Admin' => $permissions,
            'Project Manager' => [
                'view users', 'view roles',
                'view shipments', 'update shipments',
                'view installations', 'approve installations',
                'view reports', 'approve reports', 'view notifications',
            ],
            'Warehouse Manager' => [
                'manage warehouses', 'view warehouses',
                'create shipments', 'update shipments', 'view shipments',
            ],
            'Logistics Officer' => [
                'assign trucks', 'update truck location', 'view trucks',
                'view shipments',
            ],
            'Technician Lead' => [
                'create installations', 'update installations', 'view installations',
                'create reports', 'view reports',
            ],
            'Technician Assistant' => [
                'view installations', 'view reports',
            ],
            'Health Officer' => [
                'approve deliveries', 'approve installations', 'view reports',
            ],
            'Viewer' => ['view activity logs', 'view reports', 'view notifications'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        echo "✅ Roles and permissions seeded successfully.\n";
    }
}
