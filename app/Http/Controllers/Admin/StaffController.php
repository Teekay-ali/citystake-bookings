<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\StaffWelcome;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('manage-staff'), 403);

        $filters = [
            'search'   => trim((string) $request->input('search')) ?: null,
            'role'     => $request->input('role') ?: null,
            'building' => $request->input('building') ?: null,
            'status'   => $request->input('status') ?: null, // active | inactive
        ];

        $staff = User::with(['roles', 'buildings'])
            ->where('is_staff', true)
            ->when($filters['search'], fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")))
            ->when($filters['role'], fn ($q, $r) => $q->whereHas('roles', fn ($q) => $q->where('name', $r)))
            ->when($filters['building'], fn ($q, $b) => $q->whereHas('buildings', fn ($q) => $q->where('buildings.id', $b)))
            ->when($filters['status'] === 'active', fn ($q) => $q->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->select(['id', 'name', 'email', 'phone', 'is_staff', 'is_active', 'welcome_sent_at', 'created_at', 'updated_at'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Staff/Index', [
            'staff'     => $staff,
            'roles'     => Role::orderBy('name')->get(['id', 'name']),
            'buildings' => Building::where('is_active', true)->get(['id', 'name']),
            'filters'   => $filters,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-staff'), 403);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'phone'        => 'nullable|string|max:20',
            'role'         => 'required|exists:roles,name',
            'building_ids' => 'required|array|min:1',
            'building_ids.*' => 'exists:buildings,id',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'] ?? null,
                // Placeholder secret — the staffer sets their own password via
                // the invite link below; this value is never shared with anyone.
                'password' => Hash::make(Str::random(40)),
                'is_staff' => true,
                'is_active' => true,
                'email_verified_at' => now(), // Staff don't need email verification
            ]);

            $user->assignRole($validated['role']);
            $user->buildings()->sync($validated['building_ids']);

            AuditLog::log('staff.created', $user, null, ['name' => $user->name, 'email' => $user->email, 'role' => $validated['role']]);

            return $user;
        });

        // Send a tokenised "set your password" link instead of a cleartext
        // password. The token uses the standard password-reset broker.
        try {
            $token = Password::createToken($user);
            $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

            Mail::to($user->email)->send(new StaffWelcome($user, $resetUrl, $validated['role']));
            $user->update(['welcome_sent_at' => now()]);
        } catch (\Exception $e) {
            \Log::error('Failed to send staff welcome email', ['error' => $e->getMessage(), 'user_id' => $user->id]);
        }

        return redirect()->route('manage.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(User $staff)
    {
        abort_unless(auth()->user()->can('manage-staff'), 403);

        if ($staff->is_admin || ! $staff->is_staff) {
            abort(403, 'This account cannot be edited here.');
        }

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
        abort_unless(auth()->user()->can('manage-staff'), 403);

        if ($staff->is_admin || ! $staff->is_staff) {
            abort(403, 'This account cannot be edited here.');
        }

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

        AuditLog::log('staff.updated', $staff, ['name' => $staff->getOriginal('name'), 'email' => $staff->getOriginal('email')], ['name' => $validated['name'], 'email' => $validated['email'], 'role' => $validated['role']]);

        return redirect()->route('manage.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function toggleActive(User $staff)
    {
        abort_unless(auth()->user()->can('manage-staff'), 403);

        // Only staff accounts are managed here — not admins, guests or customers.
        if ($staff->is_admin || ! $staff->is_staff) {
            abort(403, 'This account cannot be managed here.');
        }

        // Prevent deactivating yourself
        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $staff->update(['is_active' => !$staff->is_active]);

        AuditLog::log($staff->is_active ? 'staff.activated' : 'staff.deactivated', $staff, null, ['is_active' => $staff->is_active]);

        return back()->with('success', $staff->is_active
            ? 'Staff member activated.'
            : 'Staff member deactivated.'
        );
    }

}
