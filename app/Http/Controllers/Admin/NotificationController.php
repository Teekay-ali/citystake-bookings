<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function page(Request $request)
    {
        $user   = auth()->user();
        $filter = $request->input('filter') === 'unread' ? 'unread' : 'all';

        $notifications = $user->notifications()
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Notifications/Index', [
            // Merge so "load more" appends pages instead of replacing the list
            'notifications' => Inertia::merge(fn () => $notifications->items()),
            'pagination'    => [
                'current'  => $notifications->currentPage(),
                'last'     => $notifications->lastPage(),
                'total'    => $notifications->total(),
                'has_more' => $notifications->hasMorePages(),
            ],
            'filter'      => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(string $id)
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
