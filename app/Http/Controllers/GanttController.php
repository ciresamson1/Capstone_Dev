<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\Carbon;

class GanttController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = strtolower((string) $user->role);

        $projectQuery = Project::with([
            'creator:id,name,email',
            'tasks.assignedTo:id,name,email',
        ]);

        if ($role === 'pm') {
            $projectQuery->where('created_by', $user->id);
        }

        $projects = $projectQuery
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [];
        foreach ($projects as $project) {
            $tasks = $project->tasks->sortBy('start_date')->values();
            $projectStart = $project->start_date ? Carbon::parse($project->start_date) : null;
            $projectEnd = $project->end_date ? Carbon::parse($project->end_date) : null;

            if (!$projectStart && $tasks->isNotEmpty()) {
                $projectStart = Carbon::parse($tasks->min('start_date'));
            }
            if (!$projectEnd && $tasks->isNotEmpty()) {
                $projectEnd = Carbon::parse($tasks->max('end_date'));
            }
            if (!$projectStart || !$projectEnd) {
                continue;
            }

            if ($projectEnd->lt($projectStart)) {
                $projectEnd = (clone $projectStart);
            }

            $projectProgress = $tasks->count() > 0
                ? (int) round(($tasks->where('progress', '>=', 100)->count() / max(1, $tasks->count())) * 100)
                : 0;

            $rows[] = [
                'id' => 'project-' . $project->id,
                'name' => '[' . ($project->unique_id ?? ('PRJ-' . $project->id)) . '] ' . $project->name,
                'start' => $projectStart->toDateString(),
                'end' => $projectEnd->toDateString(),
                'progress' => $projectProgress,
                'custom_class' => 'bar-project',
                'description' => $project->description ?? 'No description provided.',
                'owner' => $project->creator?->name ?? 'Unassigned',
                'due' => $projectEnd->format('M d, Y'),
                'type' => 'project',
                'project_id' => $project->id,
                'task_id' => null,
            ];

            foreach ($tasks as $task) {
                if (!$task->start_date || !$task->end_date) {
                    continue;
                }

                $taskStart = Carbon::parse($task->start_date);
                $taskEnd = Carbon::parse($task->end_date);
                if ($taskEnd->lt($taskStart)) {
                    $taskEnd = (clone $taskStart);
                }

                $customClass = 'bar-task-pending';
                if ((int) $task->progress >= 100 || $task->status === 'completed') {
                    $customClass = 'bar-task-completed';
                } elseif ($taskEnd->lt(Carbon::today())) {
                    $customClass = 'bar-task-overdue';
                } elseif ($task->status === 'in_progress' || (int) $task->progress > 0) {
                    $customClass = 'bar-task-progress';
                }

                $rows[] = [
                    'id' => 'task-' . $task->id,
                    'name' => '  - ' . $task->title,
                    'start' => $taskStart->toDateString(),
                    'end' => $taskEnd->toDateString(),
                    'progress' => max(0, min(100, (int) $task->progress)),
                    'custom_class' => $customClass,
                    'description' => $task->description ?? 'No description provided.',
                    'owner' => $task->assignedTo?->name ?? 'Unassigned',
                    'due' => $taskEnd->format('M d, Y'),
                    'type' => 'task',
                    'project_id' => $project->id,
                    'task_id' => $task->id,
                ];
            }
        }

        return view('gantt.index', [
            'ganttRows' => $rows,
            'isAdmin' => $role === 'admin',
        ]);
    }
}
