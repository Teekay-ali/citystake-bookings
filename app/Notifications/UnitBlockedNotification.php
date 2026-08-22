<?php

namespace App\Notifications;

use App\Models\BlockedDate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells managers a unit has been pulled out of service for maintenance during
 * a QC inspection.
 */
class UnitBlockedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public BlockedDate $blocked,
        public string $unitNumber,
        public string $buildingName,
        public bool $maintenanceRaised = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $from = $this->blocked->blocked_from?->format('j M');
        $to   = $this->blocked->blocked_to?->format('j M');

        return [
            'title'   => 'Unit blocked for maintenance',
            'message' => "Unit {$this->unitNumber} at {$this->buildingName} is blocked {$from} – {$to}: {$this->blocked->reason}."
                . ($this->maintenanceRaised ? ' A maintenance request was raised.' : ''),
            'url'     => route('manage.blocked-dates.index'),
            'icon'    => 'maintenance',
        ];
    }
}
