<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells the front desk a unit has passed QC and is ready for the next guest.
 */
class UnitGuestReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $unitNumber,
        public string $buildingName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Unit ready for guests',
            'message' => "Unit {$this->unitNumber} at {$this->buildingName} has passed inspection and is guest-ready.",
            'url'     => route('manage.housekeeping.index'),
            'icon'    => 'inspection',
        ];
    }
}
