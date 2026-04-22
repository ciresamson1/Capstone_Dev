@php
    $isSubscribed = $subscriptionActive ?? ($billingData['subscription_active'] ?? false);
@endphp

<nav class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
    @if($isSubscribed)
        <a href="{{ route('special-pm.dashboard') }}" class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('special-pm.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Dashboard</a>
    @else
        <span class="cursor-not-allowed rounded-xl px-4 py-2 text-sm font-semibold text-slate-400">Dashboard (Locked)</span>
    @endif

    <a href="{{ route('special-pm.settings') }}" class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('special-pm.settings') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">White Label Settings</a>

    @if($isSubscribed)
        <a href="{{ route('special-pm.manage-dm') }}" class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('special-pm.manage-dm') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Manage DM</a>
    @else
        <span class="cursor-not-allowed rounded-xl px-4 py-2 text-sm font-semibold text-slate-400">Manage DM (Locked)</span>
    @endif

    <a href="{{ route('special-pm.billing') }}" class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('special-pm.billing') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Billing</a>

    @unless($isSubscribed)
        <span class="rounded-xl bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-700">Pay via QR in Billing to unlock full dashboard.</span>
    @endunless

    <div class="ml-auto">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Logout</button>
        </form>
    </div>
</nav>
