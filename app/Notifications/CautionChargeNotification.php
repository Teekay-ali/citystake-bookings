<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\CautionFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CautionChargeNotification extends Notification
{
    use Queueable;

    // $action: 'added' | 'voided'
    public function __construct(
        public Booking $booking,
        public CautionFeeCharge $charge,
        public string $action = 'added',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $amount = '₦' . number_format((float) $this->charge->amount, 0);
        $label  = CautionFeeCharge::CATEGORIES[$this->charge->category] ?? $this->charge->category;

        return [
            'title'   => $this->action === 'voided' ? 'Caution charge voided' : 'Caution fee charged',
            'message' => $this->action === 'voided'
                ? "{$amount} {$label} charge on {$this->booking->guest_name}'s caution was voided."
                : "{$amount} charged to {$this->booking->guest_name}'s caution fee ({$label}).",
            'url'     => route('manage.bookings.show', $this->booking->booking_reference),
            'icon'    => 'caution_fee',
        ];
    }
}
