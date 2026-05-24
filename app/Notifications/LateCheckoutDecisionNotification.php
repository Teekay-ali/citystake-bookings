<?php
namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LateCheckoutDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public string $decision) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        $label = $this->decision === 'approved' ? 'Approved' : 'Rejected';
        return [
            'title'   => "Late Checkout {$label}",
            'message' => "Late checkout for {$this->booking->guest_name} ({$this->booking->booking_reference}) has been {$this->decision}.",
            'url'     => route('manage.bookings.show', $this->booking->id),
            'icon'    => 'late_checkout',
        ];
    }
}
