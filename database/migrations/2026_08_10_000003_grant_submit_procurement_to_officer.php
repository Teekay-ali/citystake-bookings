<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The Procurement Officer can now raise procurement requests too. Additively
 * grant submit-procurement to head-of-procurement — never touches other perms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'submit-procurement']);
        Role::where('name', 'head-of-procurement')->first()?->givePermissionTo('submit-procurement');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', 'head-of-procurement')->first();
        if ($role && $role->hasPermissionTo('submit-procurement')) {
            $role->revokePermissionTo('submit-procurement');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
