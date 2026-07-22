<?php

namespace App\Notifications;

use App\Models\InspectionRound;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InspectionRoundCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InspectionRound $round,
        public int $unitsInspected,
        public int $concerns,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $building = $this->round->building?->name ?? 'A property';
        $date     = $this->round->round_date?->format('j M Y');

        return [
            'title'   => $this->concerns > 0
                ? 'Inspection round completed with concerns'
                : 'Inspection round completed',
            'message' => "{$building} · {$date} — {$this->unitsInspected} unit"
                . ($this->unitsInspected !== 1 ? 's' : '') . ' inspected'
                . ($this->concerns > 0
                    ? ", {$this->concerns} concern" . ($this->concerns !== 1 ? 's' : '') . ' reported.'
                    : ', all clear.'),
            'url'     => route('manage.inspections.round', $this->round->id),
            'icon'    => 'inspection',
        ];
    }
}
