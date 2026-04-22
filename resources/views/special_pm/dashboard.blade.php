@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-slate-100 p-6 xl:p-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">White Label SaaS</p>
                <h1 class="text-2xl font-semibold text-slate-900">{{ $user->white_label_brand_name ?: ($user->company ?: 'Special PM Workspace') }}</h1>
                <p class="mt-1 text-sm text-slate-500">Manage your client portfolio under your own brand.</p>
            </div>
        </div>

        @include('special_pm._nav')

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-700">{{ session('status') }}</div>
        @endif

        <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Projects</p>
                <p class="mt-4 text-3xl font-bold text-slate-900">{{ $totalProjects }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Active Projects</p>
                <p class="mt-4 text-3xl font-bold text-emerald-600">{{ $activeProjects }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Tasks</p>
                <p class="mt-4 text-3xl font-bold text-slate-900">{{ $totalTasks }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Completion</p>
                <p class="mt-4 text-3xl font-bold {{ $completionRate >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $completionRate }}%</p>
                <p class="mt-1 text-xs text-slate-500">{{ $completedTasks }} completed</p>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Subscription Status</h2>
                <p class="mt-2 text-sm text-slate-500">Monthly billing powered by Stripe.</p>
                <div class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $subscriptionActive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                    {{ $subscriptionActive ? 'ACTIVE' : strtoupper($user->subscription_status ?? 'INACTIVE') }}
                </div>
                <div class="mt-5">
                    <a href="{{ route('special-pm.billing') }}" class="rounded-3xl bg-sky-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-400">Open Billing</a>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">White Label Settings</h2>
                <p class="mt-2 text-sm text-slate-500">Current branding values used by your tenant dashboard.</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Brand Name</dt>
                        <dd class="font-semibold text-slate-900">{{ $user->white_label_brand_name ?: 'Not set' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Primary Color</dt>
                        <dd class="font-semibold text-slate-900">{{ $user->white_label_primary_color }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">Accent Color</dt>
                        <dd class="font-semibold text-slate-900">{{ $user->white_label_accent_color }}</dd>
                    </div>
                </dl>
                <div class="mt-5">
                    <a href="{{ route('special-pm.settings') }}" class="rounded-3xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">Edit White Label</a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
