<?php

namespace App\Notifications;

use App\Models\PaymentApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public PaymentApproval $approval) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'payment_approval_requested',
            'title'   => 'Payment Approval Request',
            'message' => "{$this->approval->requestedBy->name} is requesting approval for a {$this->approval->type_label} payment of ₦" . number_format($this->approval->amount, 0) . " to {$this->approval->recipient_name}.",
            'url'     => route('manage.payment-approvals.show', $this->approval->id),
            'meta'    => [
                'approval_id' => $this->approval->id,
                'amount'      => $this->approval->amount,
                'type'        => $this->approval->type_label,
            ],
        ];
    }
}
