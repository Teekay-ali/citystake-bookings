<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CautionRefundRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $actionLabel = match($this->booking->caution_refund_action) {
            'full_refund'       => 'Full Refund',
            'partial_deduction' => 'Partial Deduction (₦' . number_format($this->booking->caution_refund_deduction_amount, 0) . ')',
            'full_forfeit'      => 'Full Forfeit',
            default             => 'Unknown',
        };

        return (new MailMessage)
            ->subject("Caution Refund Request - {$this->booking->booking_reference}")
            ->line("A caution fee refund request has been raised for booking {$this->booking->booking_reference}.")
            ->line("Guest: {$this->booking->guest_name}")
            ->line("Action Requested: {$actionLabel}")
            ->line("Reason: " . ($this->booking->caution_refund_reason ?? 'None provided'))
            ->action('Review Booking', url(route('manage.bookings.show', $this->booking->id)))
            ->line('Please review and process this request.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'               => 'caution_refund_requested',
            'title'              => 'Caution Refund Request',
            'booking_id'         => $this->booking->id,
            'booking_reference'  => $this->booking->booking_reference,
            'guest_name'         => $this->booking->guest_name,
            'action'             => $this->booking->caution_refund_action,
            'message'            => "Caution fee refund request for {$this->booking->booking_reference} - {$this->booking->guest_name}",
            'url'                => route('manage.bookings.show', $this->booking->id),
        ];
    }
}
