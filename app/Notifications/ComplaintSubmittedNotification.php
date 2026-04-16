<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public Complaint $complaint) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'New Complaint',
            'message' => "A new complaint was submitted for {$this->complaint->building->name}: {$this->complaint->subject}",
            'url'     => route('manage.complaints.show', $this->complaint->id),
            'icon'    => 'complaint',
        ];
    }
}
