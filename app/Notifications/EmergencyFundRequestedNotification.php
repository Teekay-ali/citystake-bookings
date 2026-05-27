<?php

namespace App\Notifications;

use App\Models\EmergencyFundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyFundRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public EmergencyFundRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $stage = $this->request->isManagerApproved() ? 'final approval' : 'first review';
        return [
            'type'    => 'emergency_fund_requested',
            'title'   => 'Emergency Fund Request',
            'message' => "{$this->request->requestedBy->name} has requested ₦" . number_format($this->request->amount, 0) . " from the {$this->request->building->name} emergency fund requiring your {$stage}. Reason: {$this->request->reason}",
            'url'     => route('manage.emergency-fund.show', $this->request->id),
            'icon'    => 'task',
            'meta'    => [
                'request_id' => $this->request->id,
                'amount'     => $this->request->amount,
                'building'   => $this->request->building->name,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Emergency Fund Request — {$this->request->building->name}")
            ->view('emails.emergency-fund.requested', [
                'request'    => $this->request,
                'notifiable' => $notifiable,
            ]);
    }
}
