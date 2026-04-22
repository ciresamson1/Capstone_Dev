<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Task $task;
    public string $taskUrl;
    public string $pmName;
    public ?string $pmEmail;

    public function __construct(Task $task, string $taskUrl, string $pmName, ?string $pmEmail)
    {
        $this->task = $task;
        $this->taskUrl = $taskUrl;
        $this->pmName = $pmName;
        $this->pmEmail = $pmEmail;
    }

    public function build()
    {
        $taskTitle = $this->task->title ?? 'a task';
        $projectName = $this->task->project?->name ?? 'your project';

        return $this->subject('SGpro.co – Task Completed: ' . $taskTitle . ' (' . $projectName . ')')
            ->view('emails.task-completed');
    }
}
