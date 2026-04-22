<?php

namespace App\Http\Controllers;

use App\Events\DashboardUpdated;
use App\Events\SubTaskChanged;
use App\Events\TaskCommentCreated;
use App\Models\ActivityLog;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SubTaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        if ((int) $task->progress >= 100 || ($task->status ?? null) === 'completed') {
            throw ValidationException::withMessages([
                'message' => 'Task is completed. Comment and link posting is disabled.',
            ]);
        }

        $role = strtolower((string) auth()->user()->role);
        if (!in_array($role, ['admin', 'pm', 'dm'], true)) {
            throw ValidationException::withMessages([
                'message' => 'Only Admin, PM, and DM can create subtasks from comments.',
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'parent_id' => 'nullable|integer|exists:task_comments,id',
        ]);

        $threadParentId = $this->resolveThreadParentId($task->id, $validated['parent_id'] ?? null);

        // Check if there's any pending approval in the task (block all new subtasks if ANY pending exists)
        if ($this->hasPendingApprovalInTask($task->id)) {
            throw ValidationException::withMessages([
                'message' => 'This task has a pending subtask approval waiting for client approval. Wait or use /resend.',
            ]);
        }

        $subTask = SubTask::create([
            'task_id' => $task->id,
            'unique_code' => $this->generateUniqueCode($task->id),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_completed' => false,
        ]);

        $clientMention = $task->project?->client?->name
            ? '@' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $task->project->client->name)
            : '@client';

        $approvalComment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'message' => 'hi (' . $clientMention . ') please review all the comments in this thread and links.',
            'link_url' => null,
            'attachment' => null,
            'type' => 'subtask_approval:' . $subTask->id . ':pending',
            'parent_id' => $threadParentId,
        ]);

        $approvalComment->load('user', 'task.project');

        try {
            broadcast(new TaskCommentCreated($approvalComment))->toOthers();
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        try {
            broadcast(new SubTaskChanged($subTask, 'created'))->toOthers();
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        ActivityLog::record(
            'created_subtask',
            'Created subtask "' . $subTask->title . '" (' . $subTask->unique_code . ') under task "' . $task->title . '"',
            $task
        );

        Cache::forget('admin_dashboard_data');
        Cache::forget('admin_dashboard_kpi_cards');
        Cache::forget('admin_dashboard_chart_data');

        try {
            broadcast(new DashboardUpdated('subtask', 'created', $subTask->id));
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        return response()->json([
            'status' => 'created',
            'subtask' => [
                'id' => $subTask->id,
                'task_id' => $subTask->task_id,
                'unique_code' => $subTask->unique_code,
                'title' => $subTask->title,
                'description' => $subTask->description,
                'start_date' => optional($subTask->start_date)->toDateString(),
                'end_date' => optional($subTask->end_date)->toDateString(),
                'is_completed' => (bool) $subTask->is_completed,
            ],
            'approval_comment' => $this->serializeComment($approvalComment),
        ]);
    }

    public function resend(Request $request, Task $task)
    {
        if ((int) $task->progress >= 100 || ($task->status ?? null) === 'completed') {
            throw ValidationException::withMessages([
                'message' => 'Task is completed. Comment and link posting is disabled.',
            ]);
        }

        $role = strtolower((string) auth()->user()->role);
        if (!in_array($role, ['admin', 'pm', 'dm'], true)) {
            throw ValidationException::withMessages([
                'message' => 'Only Admin, PM, and DM can use /resend for subtask approvals.',
            ]);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:task_comments,id',
        ]);

        $threadParentId = $this->resolveThreadParentId($task->id, $validated['parent_id'] ?? null);

        $pending = TaskComment::where('task_id', $task->id)
            ->where('type', 'like', 'subtask_approval:%:pending')
            ->when(
                is_null($threadParentId),
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $threadParentId)
            )
            ->latest('id')
            ->first();

        if (!$pending) {
            throw ValidationException::withMessages([
                'message' => 'No pending subtask approval found in this thread to resend.',
            ]);
        }

        $resentComment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'message' => $pending->message,
            'link_url' => $pending->link_url,
            'attachment' => $pending->attachment,
            'type' => $pending->type,
            'parent_id' => $threadParentId,
        ]);

        $resentComment->load('user', 'task.project');

        try {
            broadcast(new TaskCommentCreated($resentComment))->toOthers();
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        Cache::forget('admin_dashboard_data');
        Cache::forget('admin_dashboard_kpi_cards');
        Cache::forget('admin_dashboard_chart_data');

        try {
            broadcast(new DashboardUpdated('comment', 'created', $resentComment->id));
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        ActivityLog::record(
            'resent_subtask_approval',
            'Resent pending subtask approval in task "' . $task->title . '"',
            $task
        );

        return response()->json([
            'status' => 'resent',
            'approval_comment' => $this->serializeComment($resentComment),
        ]);
    }

    public function approve(SubTask $subTask)
    {
        $userRole = strtolower((string) auth()->user()->role);
        if ($userRole !== 'client') {
            throw ValidationException::withMessages([
                'message' => 'Only clients can approve subtasks.',
            ]);
        }

        if (!$subTask->is_completed) {
            $subTask->is_completed = true;
            $subTask->save();
        }

        TaskComment::where('task_id', $subTask->task_id)
            ->where('type', 'subtask_approval:' . $subTask->id . ':pending')
            ->update(['type' => 'subtask_approval:' . $subTask->id . ':completed']);

        ActivityLog::record(
            'approved_subtask',
            'Client approved subtask "' . $subTask->title . '" (' . $subTask->unique_code . ')',
            $subTask->task
        );

        Cache::forget('admin_dashboard_data');
        Cache::forget('admin_dashboard_kpi_cards');
        Cache::forget('admin_dashboard_chart_data');

        try {
            broadcast(new SubTaskChanged($subTask, 'approved'))->toOthers();
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        try {
            broadcast(new DashboardUpdated('subtask', 'approved', $subTask->id));
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        return response()->json([
            'status' => 'approved',
            'subtask' => [
                'id' => $subTask->id,
                'task_id' => $subTask->task_id,
                'unique_code' => $subTask->unique_code,
                'title' => $subTask->title,
                'description' => $subTask->description,
                'start_date' => optional($subTask->start_date)->toDateString(),
                'end_date' => optional($subTask->end_date)->toDateString(),
                'is_completed' => true,
            ],
        ]);
    }

    public function update(Request $request, SubTask $subTask)
    {
        $userRole = strtolower((string) auth()->user()->role);
        if (!in_array($userRole, ['admin', 'pm', 'dm'], true)) {
            throw ValidationException::withMessages([
                'message' => 'Only Admin, PM, and DM can edit subtasks.',
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        $subTask->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        ActivityLog::record(
            'updated_subtask',
            'Updated subtask "' . $subTask->title . '" (' . $subTask->unique_code . ') with reason: ' . $validated['reason'],
            $subTask->task
        );

        Cache::forget('admin_dashboard_data');
        Cache::forget('admin_dashboard_kpi_cards');
        Cache::forget('admin_dashboard_chart_data');

        try {
            broadcast(new SubTaskChanged($subTask->fresh('task'), 'updated'))->toOthers();
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        try {
            broadcast(new DashboardUpdated('subtask', 'updated', $subTask->id));
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        return response()->json([
            'status' => 'updated',
            'subtask' => [
                'id' => $subTask->id,
                'task_id' => $subTask->task_id,
                'unique_code' => $subTask->unique_code,
                'title' => $subTask->title,
                'description' => $subTask->description,
                'start_date' => optional($subTask->start_date)->toDateString(),
                'end_date' => optional($subTask->end_date)->toDateString(),
                'is_completed' => (bool) $subTask->is_completed,
            ],
        ]);
    }

    public function destroy(Request $request, SubTask $subTask)
    {
        $userRole = strtolower((string) auth()->user()->role);
        if (!in_array($userRole, ['admin', 'pm'], true)) {
            throw ValidationException::withMessages([
                'message' => 'Only Admin and PM can delete subtasks.',
            ]);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $subTask->loadMissing('task');
        $taskId = $subTask->task_id;
        $subTaskId = $subTask->id;
        $subTaskTitle = $subTask->title;
        $subTaskCode = $subTask->unique_code;

        $subTask->delete();

        ActivityLog::record(
            'deleted_subtask',
            'Deleted subtask "' . $subTaskTitle . '" (' . $subTaskCode . ') with reason: ' . $validated['reason'],
            $subTask->task
        );

        Cache::forget('admin_dashboard_data');
        Cache::forget('admin_dashboard_kpi_cards');
        Cache::forget('admin_dashboard_chart_data');

        try {
            broadcast(new SubTaskChanged($subTask, 'deleted'))->toOthers();
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        try {
            broadcast(new DashboardUpdated('subtask', 'deleted', $subTaskId));
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        return response()->json([
            'status' => 'deleted',
            'subtask' => [
                'id' => $subTaskId,
                'task_id' => $taskId,
            ],
        ]);
    }

    public function toggle($id)
    {
        $subTask = SubTask::findOrFail($id);

        $subTask->is_completed = !$subTask->is_completed;
        $subTask->save();

        Cache::forget('admin_dashboard_data');
        Cache::forget('admin_dashboard_kpi_cards');
        Cache::forget('admin_dashboard_chart_data');

        try {
            broadcast(new DashboardUpdated('subtask', 'toggled', $subTask->id));
        } catch (\Throwable $e) {
            // Broadcast server unavailable — continue processing
        }

        return response()->json([
            'status' => 'success',
            'is_completed' => $subTask->is_completed
        ]);
    }

    private function generateUniqueCode(int $taskId): string
    {
        do {
            $candidate = 'ST-' . $taskId . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (SubTask::where('unique_code', $candidate)->exists());

        return $candidate;
    }

    private function resolveThreadParentId(int $taskId, ?int $parentId): ?int
    {
        if (!$parentId) {
            return null;
        }

        $comment = TaskComment::where('task_id', $taskId)->findOrFail($parentId);

        return $comment->parent_id ?: $comment->id;
    }

    private function hasPendingApprovalInThread(int $taskId, ?int $threadParentId): bool
    {
        return TaskComment::where('task_id', $taskId)
            ->where('type', 'like', 'subtask_approval:%:pending')
            ->when(
                is_null($threadParentId),
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $threadParentId)
            )
            ->exists();
    }

    private function hasPendingApprovalInTask(int $taskId): bool
    {
        return TaskComment::where('task_id', $taskId)
            ->where('type', 'like', 'subtask_approval:%:pending')
            ->exists();
    }

    private function serializeComment(TaskComment $comment): array
    {
        $comment->loadMissing('user', 'task');

        return [
            'id' => $comment->id,
            'task_id' => $comment->task_id,
            'parent_id' => $comment->parent_id,
            'user_id' => $comment->user_id,
            'user_name' => $comment->user->name,
            'user_role' => $comment->user->role,
            'message' => $comment->message,
            'type' => $comment->type,
            'link_url' => $comment->link_url,
            'attachment' => $comment->attachment,
            'created_at' => $comment->created_at->toISOString(),
            'created_label' => $comment->created_at->format('M d · h:i A'),
        ];
    }
}