<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage-roles'), 403);

        $roles = Role::with(['users' => function ($q) {
            $q->select('users.id', 'users.name', 'users.email')
                ->with('buildings:id,name');
        }, 'permissions:id,name'])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $allPermissions = Permission::orderBy('name')->pluck('name');

        return Inertia::render('Admin/Roles/Index', [
            'roles'          => $roles,
            'allPermissions' => $allPermissions,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-roles'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
        ]);

        $name = strtolower(str_replace(' ', '-', trim($validated['name'])));
        Role::create(['name' => $name]);

        return back()->with('success', "Role '{$name}' created.");
    }

    public function updatePermissions(Request $request, Role $role)
    {
        abort_unless(auth()->user()->can('manage-roles'), 403);

        $validated = $request->validate([
            'permissions'   => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        // Refresh permissions cache for all users with this role
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', "Permissions updated for '{$role->name}'.");
    }

    public function destroy(Role $role)
    {
        abort_unless(auth()->user()->can('manage-roles'), 403);

        $protected = [
            'super-admin', 'manager', 'accountant',
            'ceo', 'head-of-procurement', 'receptionist', 'staff',
        ];

        if (in_array($role->name, $protected)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete a role assigned to staff members.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    public function assignRole(Request $request, User $staff)
    {
        abort_unless(auth()->user()->can('manage-roles'), 403);

        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        if ($staff->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            return back()->with('error', 'You cannot change a Super Admin\'s role.');
        }

        $staff->syncRoles([$validated['role']]);

        return back()->with('success', "{$staff->name}'s role updated.");
    }
}
