<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::with(['roles', 'buildings'])
            ->where('is_staff', true)
            ->orWhere('is_admin', true)
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Staff/Create', [
            'roles'     => Role::orderBy('name')->get(['id', 'name']),
            'buildings' => Building::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'phone'        => 'nullable|string|max:20',
            'password'     => 'required|string|min:8|confirmed',
            'role'         => 'required|exists:roles,name',
            'building_ids' => 'required|array|min:1',
            'building_ids.*' => 'exists:buildings,id',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_staff' => true,
            'is_active' => true,
            'email_verified_at' => now(), // Staff don't need email verification
        ]);

        $user->assignRole($validated['role']);
        $user->buildings()->sync($validated['building_ids']);

        return redirect()->route('manage.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(User $staff)
    {
        $staff->load(['roles', 'buildings']);

        return Inertia::render('Admin/Staff/Edit', [
            'staff'     => array_merge($staff->toArray(), [
                'role'         => $staff->roles->first()?->name,
                'building_ids' => $staff->buildings->pluck('id'),
            ]),
            'roles'     => Role::orderBy('name')->get(['id', 'name']),
            'buildings' => Building::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $staff->id,
            'phone'          => 'nullable|string|max:20',
            'password'       => 'nullable|string|min:8|confirmed',
            'role'           => 'required|exists:roles,name',
            'building_ids'   => 'required|array|min:1',
            'building_ids.*' => 'exists:buildings,id',
            'is_active'      => 'boolean',
        ]);

        $staff->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? $staff->is_active,
            ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $staff->syncRoles([$validated['role']]);
        $staff->buildings()->sync($validated['building_ids']);

        return redirect()->route('manage.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function toggleActive(User $staff)
    {
        // Prevent deactivating yourself
        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $staff->update(['is_active' => !$staff->is_active]);

        return back()->with('success', $staff->is_active
            ? 'Staff member activated.'
            : 'Staff member deactivated.'
        );
    }
}
