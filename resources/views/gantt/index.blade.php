@extends('layouts.admin')

@section('content')
<div class="min-h-screen overflow-x-hidden bg-slate-100">
    <div class="flex min-h-screen flex-col xl:flex-row">
        <aside class="w-full xl:w-80 shrink-0 bg-slate-950 text-slate-100 p-6">
            <div class="mb-10">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-3xl bg-white p-1.5"><img src="/images/sgpro-logo.webp" alt="SGpro Logo" class="h-full w-full object-contain"></div>
                    <div>
                        <h1 class="text-lg font-semibold">{{ $isAdmin ? 'PCMS Admin' : 'PCMS Portal' }}</h1>
                        <p class="text-sm text-slate-400">Project Coordination</p>
                    </div>
                </div>
                <div class="mt-6 rounded-3xl border border-slate-800 bg-slate-900 p-4">
                    <div class="text-sm text-slate-400">Signed in as</div>
                    <div class="mt-2 text-base font-semibold text-white">{{ auth()->user()->name }}</div>
                    <div class="text-sm text-slate-500">{{ auth()->user()->role }}</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigation</div>
                <nav class="space-y-2">
                    <a href="{{ $isAdmin ? route('admin.dashboard') : route('pm.dashboard') }}" class="flex items-center gap-3 rounded-3xl px-4 py-3 text-sm font-medium transition hover:bg-slate-800 text-slate-300">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-800 text-slate-100">🏠</span>
                        Dashboard
                    </a>
                    <a href="{{ route('projects.index') }}" class="flex items-center gap-3 rounded-3xl px-4 py-3 text-sm font-medium transition hover:bg-slate-800 text-slate-300">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-800 text-slate-100">📁</span>
                        Projects
                    </a>
                    <a href="{{ route('gantt.index') }}" class="flex items-center gap-3 rounded-3xl bg-slate-800 px-4 py-3 text-sm font-medium text-white shadow-lg">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-500 text-white">📅</span>
                        Gantt Chart
                    </a>
                    <a href="{{ $isAdmin ? route('admin.tasks.index') : route('pm.tasks.index') }}" class="flex items-center gap-3 rounded-3xl px-4 py-3 text-sm font-medium transition hover:bg-slate-800 text-slate-300">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-800 text-slate-100">✅</span>
                        Tasks
                    </a>
                    <a href="{{ $isAdmin ? route('admin.activity-log.index') : route('pm.activity-log.index') }}" class="flex items-center gap-3 rounded-3xl px-4 py-3 text-sm font-medium transition hover:bg-slate-800 text-slate-300">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-800 text-slate-100">📋</span>
                        Activity Log
                    </a>
                    <a href="{{ $isAdmin ? route('admin.report.index') : route('pm.report.index') }}" class="flex items-center gap-3 rounded-3xl px-4 py-3 text-sm font-medium transition hover:bg-slate-800 text-slate-300">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-800 text-slate-100">📊</span>
                        Reports
                    </a>
                </nav>
            </div>
        </aside>

        <main class="flex-1 min-w-0 p-6 xl:p-8">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">Portfolio Gantt Chart</h2>
                    <p class="mt-2 text-sm text-slate-500">Full timeline of projects and tasks. Project bars are grouped with their task bars directly below.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="setView('Day')" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Day</button>
                    <button type="button" onclick="setView('Week')" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Week</button>
                    <button type="button" onclick="setView('Month')" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Month</button>
                </div>
            </div>

            <section class="rounded-3xl bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600">
                    <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Project</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Task Completed</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Task In Progress</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>Task Overdue</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>Task Pending</span>
                </div>
                <div id="portfolio-gantt" class="overflow-auto px-2 pb-5"></div>
                @if(empty($ganttRows))
                    <p class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No project/task timeline data available.</p>
                @endif
            </section>
        </main>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/frappe-gantt/dist/frappe-gantt.css">
<style>
    #portfolio-gantt { scrollbar-gutter: stable both-edges; }
    #portfolio-gantt .gantt-container { padding-bottom: 16px; }
    #portfolio-gantt svg { border-radius: 12px; }
    .bar-wrapper.bar-project .bar { fill: #0ea5e9 !important; }
    .bar-wrapper.bar-project .bar-progress { fill: #0ea5e9 !important; }
    .bar-wrapper.bar-task-completed .bar { fill: #10b981 !important; }
    .bar-wrapper.bar-task-completed .bar-progress { fill: #10b981 !important; }
    .bar-wrapper.bar-task-progress .bar { fill: #f59e0b !important; }
    .bar-wrapper.bar-task-progress .bar-progress { fill: #f59e0b !important; }
    .bar-wrapper.bar-task-overdue .bar { fill: #ef4444 !important; }
    .bar-wrapper.bar-task-overdue .bar-progress { fill: #ef4444 !important; }
    .bar-wrapper.bar-task-pending .bar { fill: #94a3b8 !important; }
    .bar-wrapper.bar-task-pending .bar-progress { fill: #94a3b8 !important; }
</style>
<script src="https://unpkg.com/frappe-gantt/dist/frappe-gantt.umd.js"></script>
<script>
    const rows = @json($ganttRows);
    let ganttInstance = null;
    let currentViewMode = 'Week';

    const parseDate = (value) => {
        if (!value) return null;
        const d = new Date(`${value}T00:00:00`);
        return Number.isNaN(d.getTime()) ? null : d;
    };

    const formatDate = (value) => {
        if (!value) return 'N/A';
        const d = parseDate(value);
        return d ? d.toLocaleDateString() : value;
    };

    const getBounds = () => {
        let minDate = null;
        let maxDate = null;
        rows.forEach((row) => {
            const s = parseDate(row.start);
            const e = parseDate(row.end);
            if (s && (!minDate || s < minDate)) minDate = s;
            if (e && (!maxDate || e > maxDate)) maxDate = e;
        });
        if (!minDate || !maxDate) return null;
        return {
            start: minDate.toISOString().slice(0, 10),
            end: maxDate.toISOString().slice(0, 10),
        };
    };

    const popupHtml = (task) => {
        const label = task.type === 'project' ? 'Project' : 'Task';
        return `
            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-lg min-w-[240px]">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">${label}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">${task.name || ''}</p>
                <p class="mt-2 text-xs text-slate-600"><span class="font-semibold text-slate-700">Owner:</span> ${task.owner || 'Unassigned'}</p>
                <p class="mt-1 text-xs text-slate-600"><span class="font-semibold text-slate-700">Due:</span> ${task.due || formatDate(task.end)}</p>
                <p class="mt-2 text-xs text-slate-500">${task.description || 'No description provided.'}</p>
                <p class="mt-2 text-[11px] font-semibold text-sky-600">Click to open details</p>
            </div>
        `;
    };

    const openTarget = (task) => {
        if (task.type === 'project' && task.project_id) {
            window.location.href = `/projects/${task.project_id}`;
            return;
        }
        if (task.type === 'task' && task.project_id && task.task_id) {
            window.location.href = `/projects/${task.project_id}#task-wrapper-${task.task_id}`;
        }
    };

    const render = (viewMode = 'Week') => {
        currentViewMode = viewMode;
        const root = document.getElementById('portfolio-gantt');
        if (!root || rows.length === 0) return;
        root.innerHTML = '';
        const bounds = getBounds();

        ganttInstance = new Gantt('#portfolio-gantt', rows, {
            view_mode: viewMode,
            readonly: true,
            start_date: bounds?.start,
            end_date: bounds?.end,
            scroll_to: bounds?.start,
            custom_popup_html: popupHtml,
            on_click: openTarget,
        });
    };

    window.setView = (mode) => render(mode);
    render('Week');

    let ganttReloadQueued = false;

    function handleGanttRealtimeUpdate() {
        if (ganttReloadQueued) return;
        ganttReloadQueued = true;
        setTimeout(() => {
            // Full reload guarantees the latest gantt rows for project/task/subtask changes.
            window.location.reload();
        }, 300);
    }

    function registerGanttRealtimeListener() {
        const attach = () => {
            if (!window.Echo) return false;
            window.Echo.channel('dashboard').listen('.dashboard.updated', handleGanttRealtimeUpdate);
            return true;
        };

        if (!attach()) {
            const intervalId = setInterval(() => {
                if (attach()) {
                    clearInterval(intervalId);
                }
            }, 250);

            setTimeout(() => clearInterval(intervalId), 5000);
        }
    }

    registerGanttRealtimeListener();
</script>
@endsection
