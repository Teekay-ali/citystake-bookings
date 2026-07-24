<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The Head of Procurement now also performs the first approval step (before the
 * accountant). Additively grant the new approve-procurement-officer permission
 * to head-of-procurement (+ super-admin/ceo) — never touches other permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'approve-procurement-officer']);

        foreach (['head-of-procurement', 'super-admin', 'ceo'] as $roleName) {
            Role::where('name', $roleName)->first()?->givePermissionTo('approve-procurement-officer');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['head-of-procurement', 'super-admin', 'ceo'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && $role->hasPermissionTo('approve-procurement-officer')) {
                $role->revokePermissionTo('approve-procurement-officer');
            }
        }

        Permission::where('name', 'approve-procurement-officer')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
