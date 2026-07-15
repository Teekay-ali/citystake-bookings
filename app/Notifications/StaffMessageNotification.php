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
        return ['mail'];
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
