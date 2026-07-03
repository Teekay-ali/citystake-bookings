<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingModifiedNotification extends Notification
{
    use Queueable;

    // $changes: human-readable list of what changed, e.g. ["Check-in 3 Jul → 4 Jul", "Unit A1 → B2"]
    public function __construct(
        public Booking $booking,
        public array $changes,
        public ?string $editorName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Booking modified',
            'message' => "{$this->booking->guest_name}'s booking was updated"
                . ($this->editorName ? " by {$this->editorName}" : '')
                . ': ' . implode('; ', $this->changes) . '.',
            'url'     => route('manage.bookings.show', $this->booking->booking_reference),
            'icon'    => 'booking',
        ];
    }
}
