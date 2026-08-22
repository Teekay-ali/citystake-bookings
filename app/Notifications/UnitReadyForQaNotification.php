<?php

namespace App\Notifications;

use App\Models\UnitTurnover;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells QC a unit has been cleaned and is ready to inspect.
 */
class UnitReadyForQaNotification extends Notification
{
    use Queueable;

    public function __construct(public UnitTurnover $turnover)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $unit     = $this->turnover->unit?->unit_number ?? '—';
        $building = $this->turnover->building?->name ?? 'A property';

        return [
            'title'   => 'Unit ready for QA inspection',
            'message' => "Unit {$unit} at {$building} has been cleaned and is ready to inspect.",
            'url'     => route('manage.inspections.index'),
            'icon'    => 'inspection',
        ];
    }
}
