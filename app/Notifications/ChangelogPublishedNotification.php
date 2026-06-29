<?php

namespace App\Notifications;

use App\Models\Changelog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangelogPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Changelog $changelog) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Platform Update' . ($this->changelog->version ? ' ' . $this->changelog->version : '') . ' - ' . $this->changelog->title)
            ->view('emails.changelog.published', [
                'changelog'  => $this->changelog,
                'notifiable' => $notifiable,
            ]);
    }
}
