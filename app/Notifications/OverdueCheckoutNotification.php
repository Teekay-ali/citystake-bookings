<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueCheckoutNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'              => 'overdue_checkout',
            'title'             => 'Overdue Checkout',
            'message'           => "Guest {$this->booking->guest_name} (Unit {$this->booking->unit?->unit_number}) was due to check out on {$this->booking->check_out->format('M j')} but has not been checked out.",
            'url'               => route('manage.bookings.show', $this->booking->booking_reference),
            'icon'              => 'checkout',
            'booking_id'        => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
        ];
    }
}
