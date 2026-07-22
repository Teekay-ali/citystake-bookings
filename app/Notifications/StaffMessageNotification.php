<?php

namespace App\Notifications;

use App\Models\StaffMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public StaffMessage $staffMessage) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $sender  = $this->staffMessage->sender->name;
        $isReply = $this->staffMessage->parent_id !== null;
        // Link a reply to its thread root; a new message links to itself.
        $threadId = $this->staffMessage->parent_id ?? $this->staffMessage->id;
        $subject  = $this->staffMessage->subject
            ?? optional($this->staffMessage->parent)->subject
            ?? 'a message';

        return [
            'title'   => $isReply ? "New reply from {$sender}" : "New message from {$sender}",
            'message' => $isReply
                ? "{$sender} replied to \"{$subject}\"."
                : "{$sender} sent you \"{$subject}\".",
            'url'     => route('manage.staff-messages.show', $threadId),
            'icon'    => 'message',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sender  = $this->staffMessage->sender->name;
        $subject = $this->staffMessage->subject ?? 'New Message';
        $isReply = $this->staffMessage->parent_id !== null;

        return (new MailMessage)
            ->subject(($isReply ? 'Re: ' : '') . $subject . ' - ' . $sender)
            ->view('emails.staff-messages.received', [
                'staffMessage' => $this->staffMessage,
                'notifiable'   => $notifiable,
                'isReply'      => $isReply,
            ]);
    }
}
