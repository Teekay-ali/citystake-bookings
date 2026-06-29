<?php

namespace App\Http\Controllers\Admin;

use App\Services\NotificationService;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Changelog;
use App\Models\User;
use App\Notifications\ChangelogPublishedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class ChangelogController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage-changelogs'), 403);

        $changelogs = Changelog::with('author:id,name')
            ->latest()
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'title'        => $c->title,
                'version'      => $c->version,
                'type'         => $c->type,
                'send_email'   => $c->send_email,
                'published_at' => $c->published_at?->toISOString(),
                'created_at'   => $c->created_at->toISOString(),
                'author'       => $c->author->name,
                'is_published' => $c->isPublished(),
            ]);

        return Inertia::render('Admin/Changelogs/Index', [
            'changelogs' => $changelogs,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-changelogs'), 403);

        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'body'       => 'required|string|max:5000',
            'version'    => 'nullable|string|max:50',
            'type'       => 'required|in:feature,fix,improvement,security',
            'send_email' => 'boolean',
        ]);

        $changelog = Changelog::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('changelog.created', $changelog, null, ['title' => $changelog->title]);

        return back()->with('success', 'Changelog entry saved as draft.');
    }

    public function publish(Changelog $changelog)
    {
        abort_unless(auth()->user()->can('manage-changelogs'), 403);

        if ($changelog->isPublished()) {
            return back()->with('error', 'Already published.');
        }

        $changelog->update(['published_at' => now()]);

        AuditLog::log('changelog.published', $changelog, null, ['title' => $changelog->title]);

        if ($changelog->send_email) {
            $recipients = User::where('is_admin', true)
                ->where('is_active', true)
                ->get();

            NotificationService::send($recipients, new ChangelogPublishedNotification($changelog));
        }

        return back()->with('success', 'Update published' . ($changelog->send_email ? ' and email sent to admins.' : '.'));
    }

    public function destroy(Changelog $changelog)
    {
        abort_unless(auth()->user()->can('manage-changelogs'), 403);

        AuditLog::log('changelog.deleted', null, ['title' => $changelog->title], null);

        $changelog->delete();

        return back()->with('success', 'Entry deleted.');
    }

    public function markRead(Request $request)
    {
        $user = auth()->user();

        $ids = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:changelogs,id',
        ])['ids'];

        foreach ($ids as $id) {
            $user->changelogReads()->syncWithoutDetaching([$id]);
        }

        return response()->json(['ok' => true]);
    }
}
