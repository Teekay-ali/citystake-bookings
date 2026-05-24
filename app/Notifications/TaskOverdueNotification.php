<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $type // 'warning' | 'overdue' | 'escalation'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'task_overdue',
            'title'   => $this->title(),
            'message' => $this->message(),
            'url'     => route('manage.tasks.show', $this->task->id),
            'icon'    => 'task',
            'meta'    => [
                'task_id'  => $this->task->id,
                'priority' => $this->task->priority,
                'due_date' => $this->task->due_date?->toDateString(),
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title() . ' — ' . $this->task->title)
            ->view('emails.tasks.overdue', [
                'task'       => $this->task,
                'type'       => $this->type,
                'title'      => $this->title(),
                'message'    => $this->message(),
                'notifiable' => $notifiable,
            ]);
    }

    private function title(): string
    {
        return match($this->type) {
            'warning'    => 'Task Due Tomorrow',
            'overdue'    => 'Task Overdue',
            'escalation' => 'Overdue Task — Action Required',
            default      => 'Task Reminder',
        };
    }

    private function message(): string
    {
        $due = $this->task->due_date?->format('M d, Y');
        return match($this->type) {
            'warning'    => "Your task \"{$this->task->title}\" is due tomorrow ({$due}). Please ensure it's completed on time.",
            'overdue'    => "Your task \"{$this->task->title}\" was due on {$due} and is now overdue.",
            'escalation' => "Task \"{$this->task->title}\" (due {$due}) remains incomplete. Immediate attention required.",
            default      => "Task \"{$this->task->title}\" requires your attention.",
        };
    }
}
