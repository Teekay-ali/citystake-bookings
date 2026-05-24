<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Task Completed',
            'message' => "Task \"{$this->task->title}\" has been marked as completed.",
            'url'     => route('manage.tasks.show', $this->task->id),
            'icon'    => 'task',
        ];
    }
}
