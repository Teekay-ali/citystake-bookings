<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $ownerEmail = config('audit.owner_email');
        $user       = auth()->user();

        // If an owner email is configured, only that account may view the logs;
        // otherwise fall back to the legacy permission check.
        if ($ownerEmail) {
            abort_unless($user->email === $ownerEmail, 403);
        } else {
            abort_unless($user->can('view-audit-logs'), 403);
        }

        $query = AuditLog::with('user')
            ->latest();

        // Hide the owner's own actions from the log
        if ($ownerEmail) {
            $query->whereDoesntHave('user', fn ($q) => $q->where('email', $ownerEmail));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->paginate(50)->withQueryString();

        $actions = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs'    => $logs,
            'actions' => $actions,
            'filters' => $request->only(['user_id', 'action', 'date']),
        ]);
    }
}
