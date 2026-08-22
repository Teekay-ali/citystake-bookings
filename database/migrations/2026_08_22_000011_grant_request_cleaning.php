<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Front desk drives the housekeeping board. Additively grant request-cleaning to
 * the roles that run reception — never touches other permissions.
 */
return new class extends Migration
{
    private array $roles = ['receptionist', 'manager', 'super-admin', 'ceo'];

    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'request-cleaning']);

        foreach ($this->roles as $role) {
            Role::where('name', $role)->first()?->givePermissionTo('request-cleaning');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach ($this->roles as $role) {
            $r = Role::where('name', $role)->first();
            if ($r && $r->hasPermissionTo('request-cleaning')) {
                $r->revokePermissionTo('request-cleaning');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
