<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dedicated housekeeping role: clean vacated units, report faults found while
 * cleaning, and draw down supplies. Additive — creates the role and grants an
 * existing permission set; never touches other roles' permissions.
 */
return new class extends Migration
{
    private array $permissions = [
        'request-cleaning',   // housekeeping board + request/mark-cleaned/cancel
        'view-properties',    // see the units being serviced
        'view-tasks',         // their cleaning assignments
        'submit-maintenance', // flag damage found mid-clean
        'view-maintenance',
        'view-stock',         // cleaning supplies
        'log-stock-usage',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $role = Role::firstOrCreate(['name' => 'housekeeping']);
        $role->givePermissionTo($this->permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'housekeeping')->first()?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
