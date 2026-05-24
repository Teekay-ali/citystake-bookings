<?php

namespace App\Notifications;

use App\Models\PaymentApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApprovalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public PaymentApproval $approval) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $decision = $this->approval->isApproved() ? 'approved' : 'declined';
        $decider  = $this->approval->approvedBy->name;

        return [
            'type'    => 'payment_approval_decision',
            'title'   => 'Payment Request ' . ucfirst($decision),
            'message' => "{$decider} has {$decision} your payment request for ₦" . number_format($this->approval->amount, 0) . " ({$this->approval->type_label}) to {$this->approval->recipient_name}." . ($this->approval->ceo_comment ? " Comment: {$this->approval->ceo_comment}" : ''),
            'url'     => route('manage.payment-approvals.show', $this->approval->id),
            'meta'    => [
                'approval_id' => $this->approval->id,
                'decision'    => $decision,
                'amount'      => $this->approval->amount,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Request ' . ($this->approval->isApproved() ? 'Approved' : 'Declined') . ' - ' . $this->approval->type_label)
            ->view('emails.payment-approvals.decision', [
                'approval'   => $this->approval,
                'notifiable' => $notifiable,
            ]);
    }

}
