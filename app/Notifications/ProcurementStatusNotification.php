<?php

namespace App\Notifications;

use App\Models\ProcurementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProcurementStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ProcurementRequest $procurement,
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
            'url'     => route('manage.procurement.show', $this->procurement->id),
            'icon'    => 'procurement',
        ];
    }
}
