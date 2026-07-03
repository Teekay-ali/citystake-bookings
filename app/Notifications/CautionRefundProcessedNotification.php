<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CautionRefundProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $action = match($this->booking->caution_refund_action) {
            'full_refund'       => 'fully refunded',
            'partial_deduction' => '₦' . number_format($this->booking->caution_fee_deduction, 0) . ' deducted',
            'full_forfeit'      => 'fully forfeited',
            default             => 'processed',
        };

        return [
            'type'              => 'caution_refund_processed',
            'title'             => 'Caution fee processed',
            'icon'              => 'caution_fee',
            'booking_id'        => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'guest_name'        => $this->booking->guest_name,
            'message'           => "Caution fee for {$this->booking->guest_name} ({$this->booking->booking_reference}) has been {$action} by manager.",
            'url'               => route('manage.bookings.show', $this->booking->booking_reference),
        ];
    }
}
