<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);

        $admins = User::where('is_admin', true)
            ->orWhereHas('roles', fn($q) => $q->whereIn('name', ['super-admin', 'ceo', 'manager']))
            ->with('roles')
            ->withCount('bookings')
            ->latest()
            ->get();

        return Inertia::render('Admin/AdminAccounts/Index', [
            'admins' => $admins,
        ]);
    }

    public function toggleActive(User $user)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);

        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        AuditLog::log(
            $user->is_active ? 'admin.activated' : 'admin.deactivated',
            $user, null,
            ['is_active' => $user->is_active]
        );

        return back()->with('success', $user->is_active
            ? "{$user->name}'s account activated."
            : "{$user->name}'s account deactivated."
        );
    }

    public function resetPassword(User $user)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);

        $newPassword = Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        AuditLog::log('admin.password_reset', $user, null, ['reset_by' => auth()->id()]);

        return back()->with('success', "Password reset. New password: {$newPassword}");
    }
}
