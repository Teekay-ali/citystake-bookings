<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Define all permissions ───
        $permissions = [
            // Bookings
            'view-bookings',
            'create-bookings',
            'manage-bookings',
            'confirm-checkin',
            'manage-availability',
            'request-late-checkout',
            'approve-late-checkout',

            // Properties
            'manage-properties',
            'manage-blocked-dates',

            // Vendors
            'view-vendors',
            'manage-vendors',

            // Complaints
            'submit-complaints',
            'manage-complaints',

            // Maintenance
            'submit-maintenance',
            'approve-maintenance-manager',
            'approve-maintenance-accountant',
            'approve-maintenance-ceo',
            'pay-maintenance',

            // Procurement
            'submit-procurement',
            'approve-procurement-accountant',
            'approve-procurement-ceo',
            'purchase-procurement',
            'confirm-procurement-receipt',

            // Stock
            'view-stock',
            'manage-stock',
            'log-stock-usage',

            // HR
            'manage-staff',
            'manage-staff-queries',
            'manage-roles',

            // Analytics
            'view-analytics',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // ─── Define roles and their permissions ───────────────────
        $rolePermissions = [
            'super-admin' => $permissions, // all permissions

            'manager' => [
                'view-bookings', 'create-bookings', 'manage-bookings',
                'confirm-checkin', 'manage-availability',
                'request-late-checkout', 'approve-late-checkout',
                'manage-blocked-dates',
                'view-vendors', 'manage-vendors',
                'submit-complaints', 'manage-complaints',
                'submit-maintenance', 'approve-maintenance-manager',
                'submit-procurement', 'confirm-procurement-receipt',
                'view-stock', 'manage-stock', 'log-stock-usage',
                'manage-staff-queries',
                'view-analytics',
            ],

            'accountant' => [
                'view-bookings',
                'view-vendors',
                'submit-complaints', 'submit-maintenance',
                'approve-maintenance-accountant', 'pay-maintenance',
                'approve-procurement-accountant',
                'view-stock', 'log-stock-usage',
                'view-analytics',
            ],

            'ceo' => [
                'view-bookings',
                'view-vendors',
                'submit-complaints', 'submit-maintenance',
                'approve-maintenance-ceo',
                'approve-procurement-ceo',
                'view-stock',
                'view-analytics',
            ],

            'head-of-procurement' => [
                'view-bookings',
                'view-vendors', 'manage-vendors',
                'submit-complaints', 'submit-maintenance',
                'purchase-procurement',
                'view-stock', 'log-stock-usage',
            ],

            'receptionist' => [
                'view-bookings', 'create-bookings',
                'confirm-checkin', 'manage-availability',
                'request-late-checkout',
                'submit-complaints', 'submit-maintenance',
                'view-stock', 'log-stock-usage',
            ],

            'staff' => [
                'view-bookings',
                'submit-complaints', 'submit-maintenance',
                'view-stock', 'log-stock-usage',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($perms);
        }

        // ─── Assign super-admin role to existing admin user ───────
        $admin = User::where('is_admin', true)->first();
        if ($admin && !$admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
    }

}
