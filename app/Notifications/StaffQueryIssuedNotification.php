<?php
namespace App\Notifications;

use App\Models\StaffQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffQueryIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(public StaffQuery $query) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Staff Query Issued',
            'message' => "A {$this->query->type} query has been issued against you: {$this->query->subject}",
            'url'     => route('manage.staff-queries.show', $this->query->id),
            'icon'    => 'task',
        ];
    }
}
