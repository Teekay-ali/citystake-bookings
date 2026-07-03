<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CheckInDueNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $overdue = $this->booking->check_in->isBefore(now()->startOfDay());
        $unit    = $this->booking->unit?->unit_number;

        return [
            'title'   => $overdue ? 'Check-in overdue' : 'Guest awaiting check-in',
            'message' => "{$this->booking->guest_name}"
                . ($unit ? " (Unit {$unit})" : '')
                . ' has not been checked in'
                . ($overdue ? ' — arrival was ' . $this->booking->check_in->format('j M') . '.' : ' yet.'),
            'url'     => route('manage.bookings.show', $this->booking->booking_reference),
            'icon'    => 'booking',
        ];
    }
}
