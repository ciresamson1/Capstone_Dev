<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SubTask;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request)
    {
        $today = Carbon::today();

        $dashboard = Cache::remember('admin_dashboard_data', now()->addMinutes(1), function () use ($today) {
            return [
                'kpiCards' => $this->buildKpiCards($today),
                'alerts' => $this->buildAlerts($today),
                'projectHealth' => $this->buildProjectHealth($today),
                'ganttData' => $this->buildGanttData($today),
                'teamPerformance' => $this->buildTeamPerformance($today),
                'clientActivity' => $this->buildClientActivity($today),
            ];
        });

        return view('admin.dashboard', $dashboard);
    }

    public function metrics(Request $request)
    {
        $today = Carbon::today();

        $kpiCards = Cache::remember('admin_dashboard_kpi_cards', now()->addSeconds(45), function () use ($today) {
            return $this->buildKpiCards($today);
        });

        return response()->json(['kpiCards' => $kpiCards]);
    }

    public function chartData(Request $request)
    {
        $today = Carbon::today();

        $data = Cache::remember('admin_dashboard_chart_data', now()->addSeconds(45), function () use ($today) {
            return [
                'ganttData' => $this->buildGanttData($today),
                'teamPerformance' => $this->buildTeamPerformance($today),
            ];
        });

        return response()->json($data);
    }

    private function buildKpiCards(Carbon $today)
    {
        $overdueProjects = Project::whereDate('end_date', '<', $today)
            ->where('status', '!=', 'completed')
            ->count();

        $totalTasks = Task::count();
        $completedTasks = Task::where('progress', 100)->count();
        $overdueTasks = Task::whereDate('end_date', '<', $today)
            ->where('progress', '<', 100)
            ->count();

        $nearDeadlineTasks = Task::whereBetween('end_date', [$today, $today->copy()->addDays(3)])
            ->where('progress', '<', 100)
            ->count();

        $activeProjects = Project::whereDate('end_date', '>=', $today)->count();
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $clientComments = TaskComment::whereHas('user', function ($query) {
            $query->where('role', 'client');
        })->count();

        $workloadCounts = Task::whereNotNull('assigned_to')
            ->pluck('assigned_to')
            ->countBy();

        $overloadedUsers = $workloadCounts->filter(function ($count) {
            return $count >= 6;
        })->count();

        $workloadStatus = $overloadedUsers > 0 ? 'Overloaded' : 'Balanced';

        return [
            [
                'title' => 'Overdue Tasks',
                'value' => $overdueTasks,
                'color' => 'red',
                'url' => route('admin.tasks.index'),
                'note' => 'Needs immediate attention',
            ],
            [
                'title' => 'Overdue Projects',
                'value' => $overdueProjects,
                'color' => $overdueProjects > 0 ? 'red' : 'green',
                'url' => route('projects.index'),
                'note' => 'Past end date and still open',
            ],
            [
                'title' => 'Tasks Near Deadline',
                'value' => $nearDeadlineTasks,
                'color' => 'yellow',
                'url' => route('admin.tasks.index'),
                'note' => 'Due within 3 days',
            ],
            [
                'title' => 'Active Projects',
                'value' => $activeProjects,
                'color' => 'green',
                'url' => route('projects.index'),
                'note' => 'Currently in progress',
            ],
            [
                'title' => 'Overall Completion',
                'value' => $completionRate . '%',
                'color' => $completionRate >= 80 ? 'green' : ($completionRate >= 50 ? 'yellow' : 'red'),
                'url' => route('projects.index'),
                'note' => 'Average delivery status',
            ],
            [
                'title' => 'Client Comments',
                'value' => $clientComments,
                'color' => 'blue',
                'url' => route('admin.dashboard') . '#client-activity',
                'note' => 'Unread client feedback',
            ],
            [
                'title' => 'Team Workload',
                'value' => $workloadStatus,
                'color' => $workloadStatus === 'Overloaded' ? 'red' : 'green',
                'url' => route('projects.index'),
                'note' => 'Resource balance check',
            ],
        ];
    }

    private function buildAlerts(Carbon $today)
    {
        $overdueTasks = Task::with('project')
            ->whereDate('end_date', '<', $today)
            ->where('progress', '<', 100)
            ->latest()
            ->take(10)
            ->get();

        $overdueGrouped = $overdueTasks
            ->groupBy(fn ($task) => $task->project?->name ?? 'Unassigned')
            ->map(function ($tasks, $projectName) {
                return [
                    'project' => $projectName,
                    'count'   => $tasks->count(),
                    'summary' => $tasks->pluck('title')->take(3)->implode(', '),
                ];
            })
            ->values();

        $overdueItems = $overdueTasks->map(fn ($task) => [
            'task_id'    => $task->id,
            'project_id' => $task->project_id,
            'title'      => $task->title,
            'project'    => $task->project?->name ?? 'Unassigned',
        ])->values();

        $clientResponseExists = function ($q) {
            $q->from('task_comments as tc')
                ->join('users as u', 'u.id', '=', 'tc.user_id')
                ->where('u.role', '!=', 'client')
                ->whereNull('tc.parent_id')
                ->whereColumn('tc.task_id', 'tasks.id')
                ->whereRaw('tc.id = (SELECT MAX(id) FROM task_comments WHERE task_id = tasks.id AND parent_id IS NULL)');
        };

        $clientResponseCount = Task::where('progress', '<', 100)
            ->whereExists($clientResponseExists)
            ->count();

        $clientResponseTasks = Task::with('project')
            ->where('progress', '<', 100)
            ->whereExists($clientResponseExists)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($task) => [
                'task_id'    => $task->id,
                'project_id' => $task->project_id,
                'title'      => $task->title,
                'project'    => $task->project?->name ?? 'Unknown',
            ])
            ->values();

        $blockedTasksCount = Task::where('progress', '<', 30)
            ->whereDate('start_date', '<=', $today)
            ->count();

        $blockedTasks = Task::with('project')
            ->where('progress', '<', 30)
            ->whereDate('start_date', '<=', $today)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($task) => [
                'task_id'    => $task->id,
                'project_id' => $task->project_id,
                'title'      => $task->title,
                'project'    => $task->project?->name ?? 'Unknown',
            ])
            ->values();

        $recentUpdates = ActivityLog::with('user')
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($log) {
                return [
                    'title' => Str::title(str_replace('_', ' ', (string) $log->action)),
                    'details' => trim(($log->description ?? 'No description') . ' by ' . ($log->user?->name ?? 'System')),
                    'time' => $log->created_at->diffForHumans(),
                ];
            });

        return [
            [
                'label' => 'High',
                'color' => 'red',
                'headline' => 'Overdue tasks by project',
                'details' => $overdueGrouped->map(fn ($group) => $group['project'] . ' (' . $group['count'] . ')')->take(4)->implode(', '),
                'items' => $overdueItems,
            ],
            [
                'label'   => 'Medium',
                'color'   => 'yellow',
                'headline' => 'Client response needed',
                'details' => $clientResponseCount . ' tasks waiting on client feedback',
                'items'   => $clientResponseTasks,
            ],
            [
                'label' => 'Medium',
                'color' => 'yellow',
                'headline' => 'Blocked tasks',
                'details' => $blockedTasksCount . ' tasks started but below 30% progress',
                'items'   => $blockedTasks,
            ],
            [
                'label' => 'Info',
                'color' => 'blue',
                'headline' => 'Recent critical updates',
                'details' => $recentUpdates->count() . ' update(s) from latest activity',
                'items' => $recentUpdates->values(),
            ],
        ];
    }

    private function buildProjectHealth(Carbon $today)
    {
        return Project::withCount(['tasks'])
            ->latest()
            ->get()
            ->map(function ($project) use ($today) {
                $progress = $project->progress;
                $daysRemaining = $today->diffInDays($project->end_date, false);
                $status = 'On Track';
                $risk = 'Low';

                if ($daysRemaining < 0 && $progress < 100) {
                    $status = 'Delayed';
                    $risk = 'High';
                } elseif ($daysRemaining <= 7 && $progress < 80) {
                    $status = 'At Risk';
                    $risk = 'Medium';
                } elseif ($progress < 50) {
                    $status = 'At Risk';
                    $risk = 'High';
                }

                $assignedLoad = $project->tasks()->whereNotNull('assigned_to')->count();

                return [
                    'name' => $project->name,
                    'progress' => $progress,
                    'status' => $status,
                    'risk' => $risk,
                    'load' => $assignedLoad . ' tasks',
                ];
            })
            ->sortByDesc('progress')
            ->values()
            ->toArray();
    }

    private function buildGanttData(Carbon $today)
    {
        return Task::with(['project', 'assignedTo'])
            ->orderBy('start_date')
            ->take(12)
            ->get()
            ->map(function ($task) use ($today) {
                $start = Carbon::parse($task->start_date);
                $end = Carbon::parse($task->end_date);
                $offset = max(0, $today->diffInDays($start, false));
                $duration = max(1, $start->diffInDays($end) + 1);
                $status = 'On Track';
                $color = '#22c55e';

                if ($end->isPast() && $task->progress < 100) {
                    $status = 'Overdue';
                    $color = '#ef4444';
                } elseif ($start->diffInDays($today) <= 3 && $task->progress < 100) {
                    $status = 'Near Deadline';
                    $color = '#f59e0b';
                }

                return [
                    'id' => $task->id,
                    'project_id' => $task->project_id,
                    'title' => Str::limit($task->title, 30),
                    'project' => $task->project?->name ?? 'Unknown',
                    'project_description' => $task->project?->description ?? 'No description available.',
                    'project_created_at' => $task->project?->created_at?->timestamp ?? 0,
                    'assigned_to' => $task->assignedTo?->name ?? 'Unassigned',
                    'startOffset' => $offset,
                    'duration' => $duration,
                    'status' => $status,
                    'color' => $color,
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                    'project_start_offset' => $task->project?->start_date
                        ? max(0, $today->diffInDays(Carbon::parse($task->project->start_date), false))
                        : $offset,
                    'project_duration' => ($task->project?->start_date && $task->project?->end_date)
                        ? max(1, Carbon::parse($task->project->start_date)->diffInDays(Carbon::parse($task->project->end_date)) + 1)
                        : $duration,
                ];
            })
            ->values()
            ->toArray();
    }

    private function buildTeamPerformance(Carbon $today)
    {
        $users = User::whereIn('role', ['admin', 'pm', 'special_pm', 'dm', 'client'])->get();

        $performance = $users->map(function ($user) use ($today) {
            $completed = $user->tasks()->where('progress', 100)->count();
            $delayed = $user->tasks()
                ->whereDate('end_date', '<', $today)
                ->where('progress', '<', 100)
                ->count();

            $avgCompletion = $user->tasks()
                ->where('progress', 100)
                ->get()
                ->map(function ($task) {
                    return Carbon::parse($task->created_at)->diffInDays($task->updated_at);
                })
                ->avg();

            return [
                'name' => $user->name,
                'completed' => $completed,
                'delayed' => $delayed,
                'avg_completion' => $avgCompletion ? round($avgCompletion, 1) : 0,
            ];
        });

        return [
            'labels' => $performance->pluck('name')->toArray(),
            'completed' => $performance->pluck('completed')->toArray(),
            'delayed' => $performance->pluck('delayed')->toArray(),
            'avgCompletion' => $performance->pluck('avg_completion')->toArray(),
        ];
    }

    private function buildClientActivity(Carbon $today)
    {
        $recentComments = TaskComment::whereHas('user', function ($query) {
            $query->where('role', 'client');
        })
        ->with(['user', 'task.project'])
        ->latest()
        ->take(5)
        ->get()
        ->map(function ($comment) {
            return [
                'project'    => $comment->task->project?->name ?? 'Unknown',
                'project_id' => $comment->task->project?->id,
                'task_id'    => $comment->task?->id,
                'user'       => $comment->user?->name ?? 'Client',
                'message'    => Str::limit($comment->message, 80),
                'time'       => $comment->created_at->diffForHumans(),
            ];
        })
        ->values()
        ->toArray();

        $approvalComments = TaskComment::where('type', 'like', 'subtask_approval:%')
            ->get(['task_id', 'type', 'updated_at']);

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

        $approvalCards = SubTask::with(['task.project'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($subTask) use ($approvalStatusBySubTask) {
                $approvalState = $approvalStatusBySubTask[$subTask->id] ?? null;
                $isCompleted = (bool) $subTask->is_completed || ($approvalState['status'] ?? 'pending') === 'completed';

                return [
                    'subtask_id' => $subTask->id,
                    'subtask_code' => $subTask->unique_code,
                    'subtask_title' => $subTask->title,
                    'task_title' => $subTask->task?->title ?? 'Unknown Task',
                    'project' => $subTask->task?->project?->name ?? 'Unknown Project',
                    'project_id' => $subTask->task?->project?->id,
                    'task_id' => $subTask->task_id,
                    'status' => $isCompleted ? 'completed' : 'pending',
                    'label' => $isCompleted ? 'Completed' : 'Not Yet Approved',
                    'updated_at' => optional($approvalState['updated_at'] ?? $subTask->updated_at)?->diffForHumans(),
                    'task_url' => ($subTask->task?->project?->id && $subTask->task_id)
                        ? route('projects.show', $subTask->task->project->id) . '#task-wrapper-' . $subTask->task_id
                        : null,
                ];
            })
            ->values();

        $pendingApprovalCards = $approvalCards
            ->where('status', 'pending')
            ->take(4)
            ->values()
            ->toArray();

        $completedApprovalCards = $approvalCards
            ->where('status', 'completed')
            ->take(4)
            ->values()
            ->toArray();

        $pendingApprovals = $approvalCards->where('status', 'pending')->count();

        $revisionCycles = TaskComment::selectRaw('projects.name as project_name, count(task_comments.id) as cycles')
            ->join('tasks', 'tasks.id', '=', 'task_comments.task_id')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->groupBy('projects.name')
            ->orderByDesc('cycles')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'project' => $item->project_name,
                    'cycles' => $item->cycles,
                ];
            })
            ->values()
            ->toArray();

        return [
            'recentComments' => $recentComments,
            'pendingApprovals' => $pendingApprovals,
            'pendingApprovalCards' => $pendingApprovalCards,
            'completedApprovalCards' => $completedApprovalCards,
            'revisionCycles' => $revisionCycles,
        ];
    }
}
