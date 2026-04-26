<?php

use App\Models\SubTask;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Mail\TaskCommentMail;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tasks:send-overdue-reminders', function () {
    $today = Carbon::today();

    $surveyUrl = trim((string) config('services.overdue_survey_url', ''));
    if ($surveyUrl !== '' && !preg_match('#^https?://#i', $surveyUrl)) {
        $surveyUrl = 'https://' . $surveyUrl;
    }

    $sender = User::query()
        ->whereIn('role', ['admin', 'pm'])
        ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
        ->orderBy('id')
        ->first();

    if (!$sender) {
        $this->warn('No admin/pm user found to post overdue reminders.');
        return 1;
    }

    $overdueTasks = Task::with(['project.client'])
        ->whereDate('end_date', '<', $today)
        ->where('progress', '<', 100)
        ->get();

    $created = 0;
    $skipped = 0;

    foreach ($overdueTasks as $task) {
        $alreadySentToday = TaskComment::query()
            ->where('task_id', $task->id)
            ->where('type', 'overdue_reminder')
            ->whereDate('created_at', $today)
            ->exists();

        if ($alreadySentToday) {
            $skipped++;
            continue;
        }

        $clientName = trim((string) ($task->project?->client?->name ?? ''));
        $clientDisplayName = $clientName !== '' ? $clientName : 'client';
        $clientMentionTag = '@' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $clientDisplayName);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'parent_id' => null,
            'user_id' => $sender->id,
            'message' => 'Hello ' . $clientMentionTag . ', This task is overdue. To disable the email notification, please answer our survey below.',
            'link_url' => $surveyUrl !== '' ? $surveyUrl : null,
            'attachment' => null,
            'type' => 'overdue_reminder',
        ]);

        try {
            $project = $task->project;
            if ($project && (bool) $task->comment_email_enabled) {
                $threadParticipantIds = TaskComment::where('task_id', $task->id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->unique();

                $allIds = collect([
                    $project->created_by,
                    $task->assigned_to,
                    $project->client_id,
                ])->merge($threadParticipantIds)
                  ->filter()
                  ->unique()
                  ->reject(fn ($id) => $id === $sender->id);

                $adminIds = User::where('role', 'admin')
                    ->whereNotIn('id', $allIds->push($sender->id)->unique()->all())
                    ->pluck('id');

                $recipientIds = $allIds->merge($adminIds)->unique();
                $recipients = User::whereIn('id', $recipientIds)->whereNotNull('email')->get();

                foreach ($recipients as $recipient) {
                    $taskUrl = $recipient->role === 'client'
                        ? url('/client/projects/' . $project->id . '#task-wrapper-' . $task->id)
                        : url('/projects/' . $project->id . '#task-wrapper-' . $task->id);
                    Mail::to($recipient->email)->send(new TaskCommentMail($comment, $taskUrl));
                }
            }
        } catch (\Throwable $e) {
            // Mail failure is non-fatal for scheduled reminders
        }

        $created++;
    }

    $this->info("Overdue reminders created: {$created}; skipped (already sent today): {$skipped}.");
    if ($surveyUrl === '') {
        $this->warn('OVERDUE_SURVEY_URL is not set. Reminders were posted without a survey link.');
    }

    return 0;
})->purpose('Post one overdue reminder comment per overdue task each day');

Artisan::command('tasks:send-unapproved-subtask-reminders', function () {
    $today = Carbon::today();

    $approvalComments = TaskComment::with(['task.project.client', 'user'])
        ->where('type', 'like', 'subtask_approval:%')
        ->orderBy('updated_at')
        ->get();

    $latestPendingComments = [];
    foreach ($approvalComments as $comment) {
        if (!preg_match('/^subtask_approval:(\d+):(pending|completed)$/i', (string) $comment->type, $matches)) {
            continue;
        }

        $subTaskId = (int) $matches[1];
        $status = strtolower($matches[2]);
        $current = $latestPendingComments[$subTaskId] ?? null;

        if ($current && !$comment->updated_at->gt($current->updated_at)) {
            continue;
        }

        if ($status === 'completed') {
            unset($latestPendingComments[$subTaskId]);
            continue;
        }

        $latestPendingComments[$subTaskId] = $comment;
    }

    if (empty($latestPendingComments)) {
        $this->info('No pending subtask approvals found.');
        return 0;
    }

    $subTasks = SubTask::with(['task.project.client'])
        ->whereIn('id', array_keys($latestPendingComments))
        ->get()
        ->keyBy('id');

    $sent = 0;
    $skipped = 0;

    foreach ($latestPendingComments as $subTaskId => $comment) {
        $subTask = $subTasks->get($subTaskId);
        $task = $comment->task;
        $project = $task ? $task->project : null;
        $client = $project ? $project->client : null;

        if (!$subTask || !$task || !$project || !$client || !$client->email) {
            $skipped++;
            continue;
        }

        if ((bool) $subTask->is_completed || !(bool) $task->comment_email_enabled) {
            $skipped++;
            continue;
        }

        $cacheKey = 'subtask-approval-reminder:' . $today->toDateString() . ':' . $subTaskId;
        if (Cache::has($cacheKey)) {
            $skipped++;
            continue;
        }

        try {
            $taskUrl = url('/client/projects/' . $project->id . '#task-wrapper-' . $task->id);
            Mail::to($client->email)->send(new TaskCommentMail($comment, $taskUrl));
            Cache::put($cacheKey, true, $today->copy()->endOfDay());
            $sent++;
        } catch (\Throwable $e) {
            $skipped++;
        }
    }

    $this->info("Unapproved subtask emails sent: {$sent}; skipped: {$skipped}.");
    return 0;
})->purpose('Send one daily email to the client for each pending subtask approval');

Schedule::command('tasks:send-overdue-reminders')->dailyAt('08:00');
Schedule::command('tasks:send-unapproved-subtask-reminders')->dailyAt('09:00');
