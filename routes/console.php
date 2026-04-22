<?php

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Mail\TaskCommentMail;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

Schedule::command('tasks:send-overdue-reminders')->dailyAt('08:00');
