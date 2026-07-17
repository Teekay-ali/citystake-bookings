<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\RolePreview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RolePreviewController extends Controller
{
    /** Roles a super-admin can preview (their own role is pointless to preview). */
    public function options()
    {
        abort_unless(RolePreview::canPreview(auth()->user()), 403);

        return response()->json(
            Role::where('name', '!=', 'super-admin')->orderBy('name')->pluck('name')
        );
    }

    public function store(Request $request)
    {
        abort_unless(RolePreview::canPreview(auth()->user()), 403);

        $validated = $request->validate([
            'role'        => ['required', 'string', Rule::exists('roles', 'name'), Rule::notIn(['super-admin'])],
            'building_id' => ['nullable', 'integer', Rule::exists('buildings', 'id')],
        ]);

        RolePreview::start($validated['role'], $validated['building_id'] ?? null);

        AuditLog::log('role_preview.started', auth()->user(), null, [
            'role'        => $validated['role'],
            'building_id' => $validated['building_id'] ?? null,
        ]);

        return redirect()->route('manage.home')
            ->with('success', "Now viewing as {$validated['role']} (read-only).");
    }

    public function destroy()
    {
        $role = RolePreview::role();

        RolePreview::stop();

        if ($role) {
            AuditLog::log('role_preview.ended', auth()->user(), null, ['role' => $role]);
        }

        return redirect()->route('manage.home')->with('success', 'Preview ended.');
    }
}
