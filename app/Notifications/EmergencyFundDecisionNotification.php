<?php

namespace App\Notifications;

use App\Models\EmergencyFundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyFundDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public EmergencyFundRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $decision = $this->request->isApproved() ? 'approved' : 'declined';
        return [
            'type'    => 'emergency_fund_decision',
            'title'   => 'Emergency Fund Request ' . ucfirst($decision),
            'message' => "{$this->request->approvedBy->name} has {$decision} your emergency fund request of ₦" . number_format($this->request->amount, 0) . " for {$this->request->building->name}." . ($this->request->ceo_comment ? " Comment: {$this->request->ceo_comment}" : ''),
            'url'     => route('manage.emergency-fund.show', $this->request->id),
            'icon'    => 'task',
            'meta'    => [
                'request_id' => $this->request->id,
                'decision'   => $decision,
                'amount'     => $this->request->amount,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Emergency Fund Request " . ($this->request->isApproved() ? 'Approved' : 'Declined') . " - {$this->request->building->name}")
            ->view('emails.emergency-fund.decision', [
                'request'    => $this->request,
                'notifiable' => $notifiable,
            ]);
    }
}
