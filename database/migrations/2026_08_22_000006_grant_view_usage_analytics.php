<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Usage analytics (page-visit tracking) is sensitive monitoring data, so it's
 * limited to the top roles. Additively grant view-usage-analytics to super-admin
 * and ceo — never touches other permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'view-usage-analytics']);

        foreach (['super-admin', 'ceo'] as $role) {
            Role::where('name', $role)->first()?->givePermissionTo('view-usage-analytics');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['super-admin', 'ceo'] as $role) {
            $r = Role::where('name', $role)->first();
            if ($r && $r->hasPermissionTo('view-usage-analytics')) {
                $r->revokePermissionTo('view-usage-analytics');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
