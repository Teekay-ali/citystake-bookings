<?php

namespace App\Notifications;

use App\Models\MaintenanceReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MaintenanceReport $report,
        public string $title,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => route('manage.maintenance.show', $this->report->id),
            'icon'    => 'maintenance',
        ];
    }
}
