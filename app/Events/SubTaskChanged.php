<?php

namespace App\Events;

use App\Models\SubTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubTaskChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $subtask;

    public function __construct(SubTask $subTask, string $changeType = 'updated')
    {
        $subTask->loadMissing('task');

        $this->subtask = [
            'id' => $subTask->id,
            'task_id' => $subTask->task_id,
            'project_id' => $subTask->task->project_id,
            'unique_code' => $subTask->unique_code,
            'title' => $subTask->title,
            'description' => $subTask->description,
            'start_date' => optional($subTask->start_date)->toDateString(),
            'end_date' => optional($subTask->end_date)->toDateString(),
            'is_completed' => (bool) $subTask->is_completed,
            'change_type' => $changeType,
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('project.' . $this->subtask['project_id']);
    }

    public function broadcastAs(): string
    {
        return 'subtask.changed';
    }
}
