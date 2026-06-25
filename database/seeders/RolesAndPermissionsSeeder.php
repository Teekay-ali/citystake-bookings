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
            'manage-adjustments',

            // Properties
            'view-properties',
            'manage-properties',
            'create-properties',
            'manage-blocked-dates',

            // Vendors
            'view-vendors',
            'manage-vendors',

            // Complaints
            'view-complaints',
            'submit-complaints',
            'manage-complaints',

            // Maintenance
            'view-maintenance',
            'submit-maintenance',
            'approve-maintenance-manager',
            'approve-maintenance-accountant',
            'approve-maintenance-ceo',
            'pay-maintenance',

            // Procurement
            'view-procurement',
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
            'manage-guests',
            'manage-changelogs',

            // Analytics
            'view-analytics',

            // Finance
            'view-financials',
            'manage-financials',
            'manage-payment-approvals',

            // Tasks
            'manage-tasks',
            'view-tasks',

            // Emergency Funds
            'manage-emergency-fund',

            // Audit
            'view-audit-logs',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // ─── Define roles and their permissions ───────────────────
        $rolePermissions = [
            'super-admin' => $permissions,

            'manager' => [
                'view-bookings', 'create-bookings', 'manage-bookings',
                'manage-adjustments',
                'confirm-checkin', 'manage-availability',
                'request-late-checkout', 'approve-late-checkout',
                'manage-blocked-dates',
                'view-properties', 'manage-properties',
                'view-vendors', 'manage-vendors',
                'submit-complaints', 'manage-complaints', 'view-complaints',
                'submit-maintenance', 'approve-maintenance-manager', 'view-maintenance',
                'submit-procurement', 'purchase-procurement', 'confirm-procurement-receipt', 'view-procurement',
                'view-stock', 'manage-stock', 'log-stock-usage',
                'manage-staff-queries', 'manage-guests',
                'manage-emergency-fund',
                'manage-tasks', 'view-tasks',
            ],

            'accountant' => [
                'view-bookings',
                'view-vendors',
                'submit-complaints', 'view-complaints',
                'submit-maintenance', 'approve-maintenance-accountant', 'pay-maintenance', 'view-maintenance',
                'approve-procurement-accountant', 'view-procurement',
                'view-stock', 'log-stock-usage',
                'view-analytics',
                'view-financials', 'manage-financials', 'manage-payment-approvals',
                'manage-emergency-fund',
                'view-tasks',
            ],

            'ceo' => [
                'view-bookings',
                'manage-adjustments',
                'view-properties', 'manage-properties', 'create-properties',
                'view-vendors',
                'submit-complaints', 'view-complaints',
                'submit-maintenance', 'approve-maintenance-ceo', 'view-maintenance',
                'approve-procurement-ceo', 'view-procurement',
                'view-stock',
                'view-analytics',
                'view-financials', 'manage-financials', 'manage-payment-approvals',
                'manage-tasks', 'view-tasks',
                'manage-emergency-fund',
                'view-audit-logs',
                'manage-guests',
                'manage-changelogs',
            ],

            'head-of-procurement' => [
                'view-bookings',
                'view-vendors', 'manage-vendors',
                'submit-complaints', 'view-complaints',
                'submit-maintenance', 'view-maintenance',
                'purchase-procurement', 'view-procurement',
                'view-stock', 'log-stock-usage',
                'view-tasks',
            ],

            'receptionist' => [
                'view-bookings', 'create-bookings', 'manage-bookings',
                'confirm-checkin', 'manage-availability',
                'request-late-checkout', 'approve-late-checkout',
                'manage-adjustments',
                'submit-complaints', 'view-complaints',
                'submit-maintenance', 'view-maintenance',
                'view-stock', 'log-stock-usage',
                'view-tasks',
                'view-properties',
            ],

            'staff' => [
                'view-bookings',
                'submit-complaints', 'view-complaints',
                'submit-maintenance', 'view-maintenance',
                'view-stock', 'log-stock-usage',
                'view-tasks',
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
