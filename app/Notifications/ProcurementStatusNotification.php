<?php

namespace App\Notifications;

use App\Models\ProcurementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
        // Super-admins observe procurement in-app only (oversight); the people
        // who actually need to act in the chain also get an email.
        if (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('super-admin')) {
            return ['database'];
        }

        return ['database', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->title} · {$this->procurement->reference}")
            ->greeting($this->title)
            ->line($this->message)
            ->line('Request: ' . $this->procurement->title . ' (' . $this->procurement->reference . ')')
            ->line('Amount: ₦' . number_format((float) $this->procurement->total_amount, 0))
            ->action('View Request', route('manage.procurement.show', $this->procurement->id))
            ->line('Please review it in the CityStake dashboard.');
    }
}
