<?php

namespace App\Notifications;

use App\Models\BookingEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEnquiryNotification extends Notification
{
    use Queueable;

    public function __construct(public BookingEnquiry $enquiry) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'New Booking Request',
            'message' => "{$this->enquiry->guest_name} wants to book {$this->enquiry->unitType->name} at {$this->enquiry->building->name}",
            'url'     => route('manage.enquiries.show', $this->enquiry->id),
            'icon'    => 'booking',
        ];
    }
}
