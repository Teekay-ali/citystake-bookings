<?php
namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Complaint $complaint) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        $statusLabels = [
            'in_progress' => 'is being looked into',
            'resolved'    => 'has been resolved',
        ];
        $label = $statusLabels[$this->complaint->status] ?? 'has been updated';

        return [
            'title'   => 'Complaint Updated',
            'message' => "Your complaint \"{$this->complaint->title}\" {$label}.",
            'url'     => route('manage.complaints.show', $this->complaint->id),
            'icon'    => 'complaint',
        ];
    }
}
