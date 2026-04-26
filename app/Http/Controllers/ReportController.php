<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\ProgressLog;
use App\Models\TaskComment;
use App\Models\CommentReaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // ── PROJECT MANAGERS ───────────────────────────────────────────────
        $pmData = User::where('role', 'pm')
            ->with(['projects.tasks'])
            ->get()
            ->map(function ($pm) use ($today) {
                $projects      = $pm->projects;
                $totalProjects = $projects->count();
                $allTasks      = $projects->flatMap(fn($p) => $p->tasks);
                $totalTasks    = $allTasks->count();

                $overdueTasks = $allTasks->filter(
                    fn($t) => $t->end_date && Carbon::parse($t->end_date)->lt($today) && $t->progress < 100
                )->count();

                $overdueProjects = $projects->filter(function ($p) use ($today) {
                    $isCompleted = strtolower((string) ($p->status ?? '')) === 'completed';
                    return $p->end_date && Carbon::parse($p->end_date)->lt($today) && ! $isCompleted;
                })->count();

                $taskIds        = $allTasks->pluck('id');
                $timelineChanges = $taskIds->isNotEmpty()
                    ? ProgressLog::whereIn('reference_id', $taskIds)->where('type', 'task')->count()
                    : 0;

                $taskDelayRate   = $totalTasks > 0 ? round(($overdueTasks / $totalTasks) * 100) : 0;
                $onTimeRate      = max(0, 100 - $taskDelayRate);

                // Risk score 0-10 (overdue projects carry heavier weight)
                $riskScore = $this->calculatePmRiskScore(
                    $totalTasks,
                    $overdueTasks,
                    $timelineChanges,
                    $totalProjects,
                    $overdueProjects
                );

                return compact(
                    'pm', 'totalProjects', 'overdueProjects',
                    'totalTasks', 'overdueTasks', 'taskDelayRate',
                    'onTimeRate', 'timelineChanges', 'riskScore'
                );
            });

        // ── DIGITAL MARKETERS ──────────────────────────────────────────────
        $dmData = User::where('role', 'dm')
            ->with('tasks')
            ->get()
            ->map(function ($dm) use ($today) {
                $tasks        = $dm->tasks;
                $totalTasks   = $tasks->count();
                $completed    = $tasks->where('progress', 100)->count();
                $overdueTasks = $tasks->filter(
                    fn($t) => $t->end_date && Carbon::parse($t->end_date)->lt($today) && $t->progress < 100
                )->count();
                $completionRate = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;

                // Productivity: tasks completed in last 30 days (use updated_at as proxy)
                $recentCompleted = $tasks->filter(
                    fn($t) => $t->progress >= 100 && $t->updated_at && $t->updated_at->gte($today->copy()->subDays(30))
                )->count();

                $totalComments = TaskComment::where('user_id', $dm->id)->count();
                $totalReplies  = TaskComment::where('user_id', $dm->id)->whereNotNull('parent_id')->count();
                $taskIds = $tasks->pluck('id')->all();
                $approvalStatusBySubTask = $this->buildSubTaskApprovalStatusMap($taskIds);
                $revisionMetrics = $this->buildDmRevisionMetrics($taskIds, $today, $approvalStatusBySubTask);
                $revisionRate = $revisionMetrics['revision_rate'];
                $unapprovedSubtasks = $revisionMetrics['unapproved_subtasks'];
                $overdueApprovalSubtasks = $revisionMetrics['overdue_approval_subtasks'];

                // Quality score = (completed - revisions_made) / max(1, total) * 100, capped 0-100
                $qualityScore = $totalTasks > 0
                    ? max(0, min(100, round((($completed - $totalReplies) / $totalTasks) * 100)))
                    : 0;

                return compact(
                    'dm', 'totalTasks', 'completed', 'completionRate',
                    'overdueTasks', 'recentCompleted',
                    'totalComments', 'totalReplies', 'revisionRate', 'qualityScore',
                    'unapprovedSubtasks', 'overdueApprovalSubtasks'
                );
            });

        // ── CLIENTS ───────────────────────────────────────────────────────
        // Only clients assigned to at least one project, or who have commented
        $assignedClientIds  = Project::whereNotNull('client_id')->pluck('client_id');
        $commentingClientIds = TaskComment::whereNotNull('user_id')->pluck('user_id');
        $clientUserIds = $assignedClientIds->merge($commentingClientIds)->unique();
        $approvalStatusBySubTask = $this->buildSubTaskApprovalStatusMap();

        $clientData = User::where('role', 'client')
            ->whereIn('id', $clientUserIds)
            ->get()
            ->map(function ($client) use ($today, $approvalStatusBySubTask) {
                $totalComments = TaskComment::where('user_id', $client->id)->count();
                $totalReplies  = TaskComment::where('user_id', $client->id)->whereNotNull('parent_id')->count();

                // Root-level comments (first contact in thread)
                $rootComments  = TaskComment::where('user_id', $client->id)->whereNull('parent_id')->count();

                $engagementRate = $totalComments > 0 ? round(($totalReplies / $totalComments) * 100) : 0;

                // Revision requests = threads the client replied to (distinct parent threads)
                $revisionRequests = TaskComment::where('user_id', $client->id)
                    ->whereNotNull('parent_id')
                    ->distinct('parent_id')
                    ->count('parent_id');

                $approvalMetrics = $this->buildClientApprovalMetrics($client->id, $today, $approvalStatusBySubTask);
                $approvedSubtasks = $approvalMetrics['approved_subtasks'];
                $overdueApprovals = $approvalMetrics['overdue_approvals'];
                $acknowledgmentPercentage = $this->buildAcknowledgmentPercentage($approvedSubtasks, $overdueApprovals);

                // Reactions given BY the client
                $thumbsUp   = CommentReaction::where('user_id', $client->id)->where('type', 'up')->count();
                $thumbsDown = CommentReaction::where('user_id', $client->id)->where('type', 'down')->count();

                $totalProjects = Project::where('client_id', $client->id)->count();

                return compact(
                    'client', 'totalProjects', 'totalComments', 'totalReplies',
                    'rootComments', 'engagementRate', 'revisionRequests', 'approvedSubtasks', 'overdueApprovals', 'acknowledgmentPercentage',
                    'thumbsUp', 'thumbsDown'
                );
            });

        return view('admin.report', compact('pmData', 'dmData', 'clientData'));
    }

    public function dmReport()
    {
        $today        = Carbon::today();
        $myProjectIds = Task::where('assigned_to', auth()->id())->pluck('project_id')->unique();
        $myTaskIds    = Task::where('assigned_to', auth()->id())->pluck('id');

        // Clients assigned to DM's projects OR who commented on DM's tasks
        $assignedClientIds   = Project::whereIn('id', $myProjectIds)->whereNotNull('client_id')->pluck('client_id');
        $commentingClientIds = TaskComment::whereIn('task_id', $myTaskIds)->pluck('user_id');
        $clientUserIds       = $assignedClientIds->merge($commentingClientIds)->unique();
        $approvalStatusBySubTask = $this->buildSubTaskApprovalStatusMap($myTaskIds->all());

        $clientData = User::where('role', 'client')
            ->whereIn('id', $clientUserIds)
            ->get()
            ->map(function ($client) use ($myTaskIds, $myProjectIds, $today, $approvalStatusBySubTask) {
                $totalComments = TaskComment::where('user_id', $client->id)->whereIn('task_id', $myTaskIds)->count();
                $totalReplies  = TaskComment::where('user_id', $client->id)->whereIn('task_id', $myTaskIds)->whereNotNull('parent_id')->count();
                $rootComments  = TaskComment::where('user_id', $client->id)->whereIn('task_id', $myTaskIds)->whereNull('parent_id')->count();

                $engagementRate = $totalComments > 0 ? round(($totalReplies / $totalComments) * 100) : 0;

                $revisionRequests = TaskComment::where('user_id', $client->id)
                    ->whereIn('task_id', $myTaskIds)
                    ->whereNotNull('parent_id')
                    ->distinct('parent_id')
                    ->count('parent_id');

                $approvalMetrics = $this->buildClientApprovalMetrics(
                    $client->id,
                    $today,
                    $approvalStatusBySubTask,
                    $myProjectIds->all(),
                    $myTaskIds->all()
                );
                $approvedSubtasks = $approvalMetrics['approved_subtasks'];
                $overdueApprovals = $approvalMetrics['overdue_approvals'];
                $acknowledgmentPercentage = $this->buildAcknowledgmentPercentage($approvedSubtasks, $overdueApprovals);

                $thumbsUp   = CommentReaction::where('user_id', $client->id)->where('type', 'up')->count();
                $thumbsDown = CommentReaction::where('user_id', $client->id)->where('type', 'down')->count();

                $totalProjects = Project::where('client_id', $client->id)->whereIn('id', $myProjectIds)->count();

                return compact(
                    'client', 'totalProjects', 'totalComments', 'totalReplies',
                    'rootComments', 'engagementRate', 'revisionRequests', 'approvedSubtasks', 'overdueApprovals', 'acknowledgmentPercentage',
                    'thumbsUp', 'thumbsDown'
                );
            });

        return view('dm.report', compact('clientData'));
    }

    public function pmReport()
    {
        $today        = Carbon::today();
        $myProjectIds = Project::where('created_by', auth()->id())->pluck('id');
        $myTaskIds    = Task::whereIn('project_id', $myProjectIds)->pluck('id');

        // ── DIGITAL MARKETERS (only those assigned to tasks in PM's projects) ─
        $dmUserIds = Task::whereIn('project_id', $myProjectIds)
            ->whereNotNull('assigned_to')
            ->pluck('assigned_to')
            ->unique();

        $dmData = User::where('role', 'dm')
            ->whereIn('id', $dmUserIds)
            ->get()
            ->map(function ($dm) use ($today, $myProjectIds) {
                $tasks        = Task::where('assigned_to', $dm->id)
                    ->whereIn('project_id', $myProjectIds)
                    ->get();
                $totalTasks   = $tasks->count();
                $completed    = $tasks->where('progress', 100)->count();
                $overdueTasks = $tasks->filter(
                    fn($t) => $t->end_date && Carbon::parse($t->end_date)->lt($today) && $t->progress < 100
                )->count();
                $completionRate = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;

                $recentCompleted = $tasks->filter(
                    fn($t) => $t->progress >= 100 && $t->updated_at && $t->updated_at->gte($today->copy()->subDays(30))
                )->count();

                $taskIds       = $tasks->pluck('id');
                $totalComments = TaskComment::where('user_id', $dm->id)->whereIn('task_id', $taskIds)->count();
                $totalReplies  = TaskComment::where('user_id', $dm->id)->whereIn('task_id', $taskIds)->whereNotNull('parent_id')->count();
                $approvalStatusBySubTask = $this->buildSubTaskApprovalStatusMap($taskIds->all());
                $revisionMetrics = $this->buildDmRevisionMetrics($taskIds->all(), $today, $approvalStatusBySubTask);
                $revisionRate = $revisionMetrics['revision_rate'];
                $unapprovedSubtasks = $revisionMetrics['unapproved_subtasks'];
                $overdueApprovalSubtasks = $revisionMetrics['overdue_approval_subtasks'];
                $qualityScore  = $totalTasks > 0
                    ? max(0, min(100, round((($completed - $totalReplies) / $totalTasks) * 100)))
                    : 0;

                return compact(
                    'dm', 'totalTasks', 'completed', 'completionRate',
                    'overdueTasks', 'recentCompleted',
                    'totalComments', 'totalReplies', 'revisionRate', 'qualityScore',
                    'unapprovedSubtasks', 'overdueApprovalSubtasks'
                );
            });

        // ── CLIENTS (those assigned to PM's projects OR who commented on PM's tasks) ───
        $assignedClientIds = Project::where('created_by', auth()->id())
            ->whereNotNull('client_id')
            ->pluck('client_id');

        $commentingClientIds = TaskComment::whereIn('task_id', $myTaskIds)
            ->pluck('user_id');

        $clientUserIds = $assignedClientIds->merge($commentingClientIds)->unique();
        $approvalStatusBySubTask = $this->buildSubTaskApprovalStatusMap($myTaskIds->all());

        $clientData = User::where('role', 'client')
            ->whereIn('id', $clientUserIds)
            ->get()
            ->map(function ($client) use ($myTaskIds, $myProjectIds, $today, $approvalStatusBySubTask) {
                $totalComments = TaskComment::where('user_id', $client->id)->whereIn('task_id', $myTaskIds)->count();
                $totalReplies  = TaskComment::where('user_id', $client->id)->whereIn('task_id', $myTaskIds)->whereNotNull('parent_id')->count();
                $rootComments  = TaskComment::where('user_id', $client->id)->whereIn('task_id', $myTaskIds)->whereNull('parent_id')->count();

                $engagementRate = $totalComments > 0 ? round(($totalReplies / $totalComments) * 100) : 0;

                $revisionRequests = TaskComment::where('user_id', $client->id)
                    ->whereIn('task_id', $myTaskIds)
                    ->whereNotNull('parent_id')
                    ->distinct('parent_id')
                    ->count('parent_id');

                $approvalMetrics = $this->buildClientApprovalMetrics(
                    $client->id,
                    $today,
                    $approvalStatusBySubTask,
                    $myProjectIds->all(),
                    $myTaskIds->all()
                );
                $approvedSubtasks = $approvalMetrics['approved_subtasks'];
                $overdueApprovals = $approvalMetrics['overdue_approvals'];
                $acknowledgmentPercentage = $this->buildAcknowledgmentPercentage($approvedSubtasks, $overdueApprovals);

                $thumbsUp   = CommentReaction::where('user_id', $client->id)->where('type', 'up')->count();
                $thumbsDown = CommentReaction::where('user_id', $client->id)->where('type', 'down')->count();

                $totalProjects = Project::where('client_id', $client->id)->whereIn('id', $myProjectIds)->count();

                return compact(
                    'client', 'totalProjects', 'totalComments', 'totalReplies',
                    'rootComments', 'engagementRate', 'revisionRequests', 'approvedSubtasks', 'overdueApprovals', 'acknowledgmentPercentage',
                    'thumbsUp', 'thumbsDown'
                );
            });

        return view('pm.report', compact('dmData', 'clientData'));
    }

    public function pdf($userId)
    {
        $today = Carbon::today();
        $user  = User::findOrFail($userId);
        $role  = $user->role;

        $kpis     = [];
        $projects = collect();
        $tasks    = collect();

        if ($role === 'pm') {
            $user->load('projects.tasks');
            $projects  = $user->projects;
            $allTasks  = $projects->flatMap(fn($p) => $p->tasks);

            $totalProjects   = $projects->count();
            $totalTasks      = $allTasks->count();
            $overdueTasks    = $allTasks->filter(fn($t) => $t->end_date && Carbon::parse($t->end_date)->lt($today) && $t->progress < 100)->count();
            $overdueProjects = $projects->filter(function ($p) use ($today) {
                $isCompleted = strtolower((string) ($p->status ?? '')) === 'completed';
                return $p->end_date && Carbon::parse($p->end_date)->lt($today) && ! $isCompleted;
            })->count();
            $taskIds         = $allTasks->pluck('id');
            $timelineChanges = $taskIds->isNotEmpty() ? ProgressLog::whereIn('reference_id', $taskIds)->where('type', 'task')->count() : 0;
            $taskDelayRate   = $totalTasks > 0 ? round(($overdueTasks / $totalTasks) * 100) : 0;
            $onTimeRate      = max(0, 100 - $taskDelayRate);
            $riskScore       = $this->calculatePmRiskScore(
                $totalTasks,
                $overdueTasks,
                $timelineChanges,
                $totalProjects,
                $overdueProjects
            );

            $kpis = compact('totalProjects', 'overdueProjects', 'totalTasks', 'overdueTasks',
                            'taskDelayRate', 'onTimeRate', 'timelineChanges', 'riskScore');

            // Build flat task table: project → task
            $tasks = $allTasks->map(function ($t) use ($today, $projects) {
                $projectName = $t->relationLoaded('project') ? optional($t->project)->name : ($projects->firstWhere('id', $t->project_id)?->name ?? '—');
                $overdue = $t->end_date && Carbon::parse($t->end_date)->lt($today) && $t->progress < 100;
                return [
                    'project'  => $t->project?->name ?? ($projects->firstWhere('id', $t->project_id)?->name ?? '—'),
                    'title'    => $t->title,
                    'progress' => $t->progress . '%',
                    'status'   => $t->progress >= 100 ? 'Completed' : ($overdue ? 'Overdue' : ($t->progress > 0 ? 'In Progress' : 'Not Started')),
                    'due'      => $t->end_date ? Carbon::parse($t->end_date)->format('M d, Y') : '—',
                ];
            });

        } elseif ($role === 'dm') {
            $user->load('tasks.project');
            $userTasks   = $user->tasks;
            $totalTasks  = $userTasks->count();
            $completed   = $userTasks->where('progress', 100)->count();
            $overdueTasks = $userTasks->filter(fn($t) => $t->end_date && Carbon::parse($t->end_date)->lt($today) && $t->progress < 100)->count();
            $completionRate  = $totalTasks > 0 ? round(($completed / $totalTasks) * 100) : 0;
            $recentCompleted = $userTasks->filter(fn($t) => $t->progress >= 100 && $t->updated_at && $t->updated_at->gte($today->copy()->subDays(30)))->count();
            $totalComments   = TaskComment::where('user_id', $user->id)->count();
            $totalReplies    = TaskComment::where('user_id', $user->id)->whereNotNull('parent_id')->count();
            $dmTaskIds = $userTasks->pluck('id')->all();
            $approvalStatusBySubTask = $this->buildSubTaskApprovalStatusMap($dmTaskIds);
            $revisionMetrics = $this->buildDmRevisionMetrics($dmTaskIds, $today, $approvalStatusBySubTask);
            $revisionRate = $revisionMetrics['revision_rate'];
            $unapprovedSubtasks = $revisionMetrics['unapproved_subtasks'];
            $overdueApprovalSubtasks = $revisionMetrics['overdue_approval_subtasks'];
            $qualityScore    = $totalTasks > 0 ? max(0, min(100, round((($completed - $totalReplies) / $totalTasks) * 100))) : 0;

            $kpis = compact('totalTasks', 'completed', 'completionRate', 'overdueTasks',
                            'recentCompleted', 'totalComments', 'totalReplies', 'revisionRate', 'qualityScore',
                            'unapprovedSubtasks', 'overdueApprovalSubtasks');

            $tasks = $userTasks->map(function ($t) use ($today) {
                $overdue = $t->end_date && Carbon::parse($t->end_date)->lt($today) && $t->progress < 100;
                return [
                    'project'  => optional($t->project)->name ?? '—',
                    'title'    => $t->title,
                    'progress' => $t->progress . '%',
                    'status'   => $t->progress >= 100 ? 'Completed' : ($overdue ? 'Overdue' : ($t->progress > 0 ? 'In Progress' : 'Not Started')),
                    'due'      => $t->end_date ? Carbon::parse($t->end_date)->format('M d, Y') : '—',
                ];
            });

        } elseif ($role === 'client') {
            $user->load(['comments' => fn($q) => $q->with('task.project')->latest()->take(50)]);
            $userComments    = $user->comments;
            $totalComments   = TaskComment::where('user_id', $user->id)->count();
            $totalReplies    = TaskComment::where('user_id', $user->id)->whereNotNull('parent_id')->count();
            $rootComments    = TaskComment::where('user_id', $user->id)->whereNull('parent_id')->count();
            $engagementRate  = $totalComments > 0 ? round(($totalReplies / $totalComments) * 100) : 0;
            $revisionRequests = TaskComment::where('user_id', $user->id)->whereNotNull('parent_id')->distinct('parent_id')->count('parent_id');
            $approvalStatusBySubTask = $this->buildSubTaskApprovalStatusMap();
            $approvalMetrics = $this->buildClientApprovalMetrics($user->id, $today, $approvalStatusBySubTask);
            $approvedSubtasks = $approvalMetrics['approved_subtasks'];
            $overdueApprovals = $approvalMetrics['overdue_approvals'];
            $acknowledgmentPercentage = $this->buildAcknowledgmentPercentage($approvedSubtasks, $overdueApprovals);

            $projects      = Project::where('client_id', $user->id)->with(['creator', 'tasks'])->get();
            $totalProjects = $projects->count();

            $kpis = compact('totalProjects', 'totalComments', 'totalReplies', 'rootComments',
                            'engagementRate', 'revisionRequests', 'approvedSubtasks', 'overdueApprovals', 'acknowledgmentPercentage');

            // Comment activity table
            $tasks = $userComments->map(function ($c) {
                return [
                    'project' => optional(optional($c->task)->project)->name ?? '—',
                    'title'   => optional($c->task)->title ?? '—',
                    'message' => \Illuminate\Support\Str::limit($c->message ?? '', 60),
                    'date'    => $c->created_at->format('M d, Y h:i A'),
                ];
            })->unique(fn($r) => $r['project'] . '|' . $r['title'])->values();
        }

        return view('admin.report-pdf', compact('user', 'role', 'kpis', 'projects', 'tasks'));
    }

    private function buildSubTaskApprovalStatusMap(array $taskIds = []): array
    {
        $query = TaskComment::where('type', 'like', 'subtask_approval:%');
        if (!empty($taskIds)) {
            $query->whereIn('task_id', $taskIds);
        }

        $approvalComments = $query->get(['task_id', 'type', 'updated_at']);

        $approvalStatusBySubTask = [];
        foreach ($approvalComments as $comment) {
            if (!preg_match('/^subtask_approval:(\d+):(pending|completed)$/i', (string) $comment->type, $matches)) {
                continue;
            }

            $subTaskId = (int) $matches[1];
            $status = strtolower($matches[2]);
            $current = $approvalStatusBySubTask[$subTaskId] ?? null;

            if (!$current || $comment->updated_at->gt($current['updated_at'])) {
                $approvalStatusBySubTask[$subTaskId] = [
                    'status' => $status,
                    'updated_at' => $comment->updated_at,
                ];
            }
        }

        return $approvalStatusBySubTask;
    }

    private function calculatePmRiskScore(
        int $totalTasks,
        int $overdueTasks,
        int $timelineChanges,
        int $totalProjects,
        int $overdueProjects
    ): float {
        $taskRisk = $totalTasks > 0 ? ($overdueTasks / $totalTasks) * 4 : 0;
        $timelineRisk = $totalTasks > 0 ? min(2.5, ($timelineChanges / max(1, $totalTasks)) * 2.5) : 0;
        $overdueProjectRisk = $totalProjects > 0 ? ($overdueProjects / $totalProjects) * 3.5 : 0;
        $overdueProjectPenalty = $overdueProjects > 0 ? min(2.5, $overdueProjects * 0.8) : 0;

        return min(10, round($taskRisk + $timelineRisk + $overdueProjectRisk + $overdueProjectPenalty, 1));
    }

    private function buildClientApprovalMetrics(
        int $clientId,
        Carbon $today,
        array $approvalStatusBySubTask,
        array $projectIds = [],
        array $taskIds = []
    ): array {
        $query = SubTask::query()
            ->with('task.project')
            ->whereHas('task.project', function ($projectQuery) use ($clientId, $projectIds) {
                $projectQuery->where('client_id', $clientId);
                if (!empty($projectIds)) {
                    $projectQuery->whereIn('id', $projectIds);
                }
            });

        if (!empty($taskIds)) {
            $query->whereIn('task_id', $taskIds);
        }

        $subTasks = $query->get()
            ->filter(fn ($subTask) => isset($approvalStatusBySubTask[$subTask->id]));

        $approvedSubtasks = $subTasks->filter(function ($subTask) use ($approvalStatusBySubTask) {
            $status = $approvalStatusBySubTask[$subTask->id]['status'] ?? 'pending';
            return $status === 'completed' || (bool) $subTask->is_completed;
        })->count();

        $overdueApprovals = $subTasks->filter(function ($subTask) use ($approvalStatusBySubTask, $today) {
            $status = $approvalStatusBySubTask[$subTask->id]['status'] ?? 'pending';
            $isCompleted = $status === 'completed' || (bool) $subTask->is_completed;
            if ($isCompleted) {
                return false;
            }

            return $subTask->end_date && Carbon::parse($subTask->end_date)->lt($today);
        })->count();

        return [
            'approved_subtasks' => $approvedSubtasks,
            'overdue_approvals' => $overdueApprovals,
        ];
    }

    private function buildDmRevisionMetrics(array $taskIds, Carbon $today, array $approvalStatusBySubTask): array
    {
        if (empty($taskIds)) {
            return [
                'revision_rate' => 0,
                'unapproved_subtasks' => 0,
                'overdue_approval_subtasks' => 0,
            ];
        }

        $approvalSubTasks = SubTask::whereIn('task_id', $taskIds)
            ->get()
            ->filter(fn ($subTask) => isset($approvalStatusBySubTask[$subTask->id]));

        $approvalTotal = $approvalSubTasks->count();
        if ($approvalTotal === 0) {
            return [
                'revision_rate' => 0,
                'unapproved_subtasks' => 0,
                'overdue_approval_subtasks' => 0,
            ];
        }

        $unapprovedSubtasks = $approvalSubTasks->filter(function ($subTask) use ($approvalStatusBySubTask) {
            return ($approvalStatusBySubTask[$subTask->id]['status'] ?? 'pending') !== 'completed';
        });

        $overdueApprovalSubtasks = $unapprovedSubtasks->filter(function ($subTask) use ($today) {
            return $subTask->end_date && Carbon::parse($subTask->end_date)->lt($today);
        })->count();

        $revisionRate = round((($unapprovedSubtasks->count() + $overdueApprovalSubtasks) / (2 * $approvalTotal)) * 100);

        return [
            'revision_rate' => (int) max(0, min(100, $revisionRate)),
            'unapproved_subtasks' => $unapprovedSubtasks->count(),
            'overdue_approval_subtasks' => $overdueApprovalSubtasks,
        ];
    }

    private function buildAcknowledgmentPercentage(int $approvedSubtasks, int $overdueApprovals): int
    {
        $totalSignals = $approvedSubtasks + $overdueApprovals;
        if ($totalSignals <= 0) {
            return 0;
        }

        return (int) round(($approvedSubtasks / $totalSignals) * 100);
    }
}
