<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Usage analytics is now super-admin only. Additively revoke
 * view-usage-analytics from every other role (it was granted to ceo), keeping
 * it on super-admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'view-usage-analytics']);

        foreach (Role::all() as $role) {
            if ($role->name !== 'super-admin' && $role->hasPermissionTo('view-usage-analytics')) {
                $role->revokePermissionTo('view-usage-analytics');
            }
        }

        Role::where('name', 'super-admin')->first()?->givePermissionTo('view-usage-analytics');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Restore CEO access.
        Role::where('name', 'ceo')->first()?->givePermissionTo('view-usage-analytics');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
