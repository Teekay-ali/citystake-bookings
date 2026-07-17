<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Additively introduces the Quality Control role and inspection permissions.
 *
 * This is deliberately a migration, NOT a seeder run: the RolesAndPermissions
 * seeder uses syncPermissions() and would overwrite role permissions edited
 * directly in production. Everything here only ADDS — it never removes an
 * existing permission from a role.
 */
return new class extends Migration
{
    private array $permissions = [
        'view-inspections',
        'conduct-inspections',
        'manage-inspections',
    ];

    public function up(): void
    {
        // 1. Ensure the permissions exist.
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // 2. Quality Control role gets view + conduct (+ view-properties for context).
        $qc = Role::firstOrCreate(['name' => 'quality-control']);
        $qc->givePermissionTo(array_filter([
            'view-inspections',
            'conduct-inspections',
            Permission::where('name', 'view-properties')->exists() ? 'view-properties' : null,
        ]));

        // 3. Grant existing privileged roles the inspection permissions (additive).
        foreach (['super-admin', 'ceo'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($this->permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['super-admin', 'ceo', 'quality-control'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->revokePermissionTo(
                    Permission::whereIn('name', $this->permissions)->get()
                );
            }
        }

        // Remove the QC role only if no users are still assigned to it.
        $qc = Role::where('name', 'quality-control')->first();
        if ($qc && $qc->users()->count() === 0) {
            $qc->delete();
        }

        Permission::whereIn('name', $this->permissions)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
