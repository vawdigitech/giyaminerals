<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions by module
        $modules = [
            'employees' => ['view', 'create', 'edit', 'delete'],
            'attendance' => ['view', 'create', 'edit', 'delete', 'export'],
            'projects' => ['view', 'create', 'edit', 'delete'],
            'tasks' => ['view', 'create', 'edit', 'delete'],
            'inventory' => ['view', 'create', 'edit', 'delete'],
            'warehouses' => ['view', 'create', 'edit', 'delete'],
            'sites' => ['view', 'create', 'edit', 'delete'],
            'transfers' => ['view', 'create', 'edit', 'delete'],
            'reports' => ['view'],
            'analytics' => ['view'],
            'issues' => ['view', 'create', 'edit', 'delete'],
            'designations' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'users' => ['view', 'create', 'edit', 'delete'],
        ];

        // Create permissions
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Create roles and assign permissions
        // Admin - Full access
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // Manager - Manage most resources except system settings
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerPermissions = Permission::whereNotIn('name', [
            'roles.create', 'roles.edit', 'roles.delete',
            'users.create', 'users.edit', 'users.delete',
        ])->pluck('name')->toArray();
        $managerRole->syncPermissions($managerPermissions);

        // Supervisor - Site-level operations
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisorPermissions = [
            'employees.view',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.export',
            'projects.view',
            'tasks.view', 'tasks.create', 'tasks.edit',
            'inventory.view',
            'warehouses.view',
            'sites.view',
            'transfers.view', 'transfers.create',
            'reports.view',
            'analytics.view',
            'issues.view', 'issues.create', 'issues.edit',
            'designations.view',
        ];
        $supervisorRole->syncPermissions($supervisorPermissions);

        // Viewer - Read-only access
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerPermissions = Permission::where('name', 'like', '%.view')->pluck('name')->toArray();
        $viewerRole->syncPermissions($viewerPermissions);

        // Assign admin role to existing admin user
        $adminUser = \App\Models\User::where('email', 'admin@judsonassociates.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }
    }
}
