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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define roles
        $roles = [
            'super-admin',
            'manager',
            'accountant',
            'ceo',
            'head-of-procurement',
            'receptionist',
            'staff',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Assign super-admin role to existing admin user
        $admin = User::where('is_admin', true)->first();
        if ($admin && !$admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
    }
}
