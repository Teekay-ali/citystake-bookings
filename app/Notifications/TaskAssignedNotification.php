<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Task Assigned',
            'message' => "You have been assigned a task: {$this->task->title}",
            'url'     => route('manage.tasks.show', $this->task->id),
            'icon'    => 'task',
        ];
    }
}
