<?php

namespace App\Http\Controllers\Admin;

use App\Services\NotificationService;

use App\Http\Controllers\Controller;
use App\Models\StaffMessage;
use App\Models\User;
use App\Notifications\StaffMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffMessageController extends Controller
{
    private function staffOnly(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->is_staff || $user->is_admin), 403);
    }

    public function index(Request $request)
    {
        $this->staffOnly();
        $user = auth()->user();

        $inbox = StaffMessage::whereHas('recipients', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->whereNull('staff_message_recipients.deleted_at');
        })
            ->whereNull('parent_id') // top-level only
            ->with('sender:id,name')
            ->withCount('replies')
            ->latest()
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $user));

        $sent = StaffMessage::where('sender_id', $user->id)
            ->whereNull('parent_id')
            ->with(['sender:id,name', 'recipients:id,name'])
            ->withCount('replies')
            ->latest()
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $user, sent: true));

        $users = User::where(fn($q) => $q->where('is_staff', true)->orWhere('is_admin', true))
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);

        $roles = Role::orderBy('name')->pluck('name');

        // Optionally load a selected thread inline (split-panel chat)
        $activeThread = null;
        if ($request->filled('thread')) {
            $message = StaffMessage::whereNull('parent_id')->find($request->thread);
            if ($message) {
                $isRecipient = $message->recipients()->where('user_id', $user->id)->exists();
                $isSender    = $message->sender_id === $user->id;

                if ($isRecipient || $isSender) {
                    if ($isRecipient) {
                        $message->recipients()->updateExistingPivot($user->id, ['read_at' => now()]);
                    }
                    $message->load(['sender:id,name', 'recipients:id,name', 'replies.sender:id,name']);
                    foreach ($message->replies as $reply) {
                        if ($reply->recipients()->where('user_id', $user->id)->exists()) {
                            $reply->recipients()->updateExistingPivot($user->id, ['read_at' => now()]);
                        }
                    }
                    $activeThread = $this->formatThread($message, $user);
                }
            }
        }

        return Inertia::render('Admin/StaffMessages/Index', [
            'inbox'        => $inbox,
            'sent'         => $sent,
            'staffUsers'   => $users,
            'roles'        => $roles,
            'activeThread' => $activeThread,
            'filters'      => ['tab' => $request->tab, 'thread' => $request->thread],
        ]);
    }

    private function formatThread(StaffMessage $message, User $user): array
    {
        return [
            'id'             => $message->id,
            'subject'        => $message->subject,
            'body'           => $message->body,
            'broadcast_role' => $message->broadcast_role,
            'created_at'     => $message->created_at->toISOString(),
            'sender'         => $message->sender,
            'recipients'     => $message->recipients->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
            'is_mine'        => $message->sender_id === $user->id,
            'replies'        => $message->replies->map(fn ($r) => [
                'id'         => $r->id,
                'body'       => $r->body,
                'created_at' => $r->created_at->toISOString(),
                'sender'     => $r->sender,
                'is_mine'    => $r->sender_id === $user->id,
            ]),
        ];
    }

    public function show(StaffMessage $staffMessage)
    {
        // Threads are now shown inline in the split-panel index
        return redirect()->route('manage.staff-messages.index', ['thread' => $staffMessage->id]);
    }

    public function store(Request $request)
    {
        $this->staffOnly();

        $validated = $request->validate([
            'subject'        => 'nullable|string|max:255',
            'body'           => 'required|string|max:5000',
            'recipient_type' => 'required|in:user,role',
            'recipient_id'   => 'required_if:recipient_type,user|nullable|exists:users,id',
            'recipient_role' => 'required_if:recipient_type,role|nullable|string|exists:roles,name',
        ]);

        if ($validated['recipient_type'] === 'role' && $validated['recipient_role'] === 'super-admin') {
            if (!auth()->user()->hasRole('super-admin')) {
                return back()->with('error', 'You cannot broadcast to Admin.');
            }
        }

        $sender = auth()->user();

        $message = StaffMessage::create([
            'sender_id'      => $sender->id,
            'subject'        => $validated['subject'] ?? null,
            'body'           => $validated['body'],
            'broadcast_role' => $validated['recipient_type'] === 'role' ? $validated['recipient_role'] : null,
        ]);

        // Resolve recipients
        if ($validated['recipient_type'] === 'user') {
            $recipients = User::where('id', $validated['recipient_id'])->get();
        } else {
            $recipients = User::role($validated['recipient_role'])
                ->where('is_active', true)
                ->where('id', '!=', $sender->id)
                ->get();
        }

        $message->recipients()->sync($recipients->pluck('id')->toArray());

        // Email notification
        NotificationService::send($recipients, new StaffMessageNotification($message));

        return back()->with('success', 'Message sent.');
    }

    public function reply(Request $request, StaffMessage $staffMessage)
    {
        $this->staffOnly();
        $user = auth()->user();

        // Only sender or recipients can reply
        $isRecipient = $staffMessage->recipients()->where('user_id', $user->id)->exists();
        $isSender    = $staffMessage->sender_id === $user->id;
        abort_unless($isRecipient || $isSender, 403);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $reply = StaffMessage::create([
            'sender_id' => $user->id,
            'body'      => $validated['body'],
            'parent_id' => $staffMessage->id,
        ]);

        // Reply goes to original sender + all recipients except self
        $originalSender = User::find($staffMessage->sender_id);
        $originalRecipients = $staffMessage->recipients()->pluck('users.id')->toArray();

        $replyRecipientIds = collect($originalRecipients)
            ->push($originalSender->id)
            ->unique()
            ->reject(fn($id) => $id === $user->id)
            ->values()
            ->toArray();

        $reply->recipients()->sync($replyRecipientIds);

        $replyRecipients = User::whereIn('id', $replyRecipientIds)->get();
        NotificationService::send($replyRecipients, new StaffMessageNotification($reply));

        return back()->with('success', 'Reply sent.');
    }

    public function destroy(StaffMessage $staffMessage)
    {
        $this->staffOnly();
        $user = auth()->user();

        // Soft-delete for recipient (hide from inbox only)
        $isRecipient = $staffMessage->recipients()->where('user_id', $user->id)->exists();
        if ($isRecipient) {
            $staffMessage->recipients()->updateExistingPivot($user->id, ['deleted_at' => now()]);
            return back()->with('success', 'Message removed from inbox.');
        }

        // Hard delete only if sender and no replies yet
        if ($staffMessage->sender_id === $user->id) {

            if ($staffMessage->replies()->count() > 0) {
                return back()->with('error', 'Cannot delete a message that has replies.');
            }

            $staffMessage->delete();
            return back()->with('success', 'Message deleted.');
        }

        abort(403);
    }

    public function unreadCount()
    {
        $this->staffOnly();
        $user = auth()->user();

        $count = $user->receivedMessages()
            ->whereNull('staff_message_recipients.read_at')
            ->whereNull('staff_message_recipients.deleted_at')
            ->whereNull('staff_messages.parent_id')
            ->count();

        return response()->json(['count' => $count]);
    }

    private function formatMessage(StaffMessage $m, User $user, bool $sent = false): array
    {
        $pivot = !$sent
            ? $m->recipients()->where('user_id', $user->id)->first()?->pivot
            : null;

        return [
            'id'             => $m->id,
            'subject'        => $m->subject ?? '(no subject)',
            'body'           => $m->body,
            'broadcast_role' => $m->broadcast_role,
            'created_at'     => $m->created_at->toISOString(),
            'sender'         => $m->sender,
            'recipients'     => $sent ? $m->recipients->map(fn($r) => ['id' => $r->id, 'name' => $r->name]) : [],
            'replies_count'  => $m->replies_count,
            'is_read'        => $sent ? true : $pivot?->read_at !== null,
            'is_mine'        => $m->sender_id === $user->id,
        ];
    }
}
