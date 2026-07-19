<?php

namespace App\Notifications;

use App\Models\UnitInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InspectionCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public UnitInspection $inspection) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $unit     = $this->inspection->unit?->unit_number;
        $building = $this->inspection->building?->name;
        $concerns = $this->inspection->overall_result === 'concerns'
            ? $this->inspection->findings()->count()
            : 0;

        $location = trim(($building ? $building : '') . ($unit ? " · Unit {$unit}" : ''));

        return [
            'title'   => $concerns > 0 ? 'Inspection completed with concerns' : 'Inspection completed',
            'message' => ($location ?: 'A unit')
                . ($concerns > 0
                    ? " was inspected and {$concerns} concern" . ($concerns !== 1 ? 's were' : ' was') . ' reported.'
                    : ' was inspected - Everything OK.'),
            'url'     => route('manage.inspections.show', $this->inspection->id),
            'icon'    => 'inspection',
        ];
    }
}
