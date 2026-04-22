@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-slate-100 p-6 xl:p-8">
    <div class="mx-auto max-w-4xl">
        @php
            $subscriptionActive = $billingData['subscription_active'] ?? false;
        @endphp

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Special PM SaaS</p>
                <h1 class="text-2xl font-semibold text-slate-900">Billing & Subscription</h1>
                <p class="mt-1 text-sm text-slate-500">Monthly Stripe subscription for your white-label dashboard.</p>
            </div>
        </div>

        @include('special_pm._nav')

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-700">{{ session('status') }}</div>
        @endif

        @if(!$subscriptionActive)
            <section class="mb-6 rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-amber-800">Payment Required To Unlock Full Access</h2>
                <p class="mt-2 text-sm text-amber-700">Scan this QR code to pay your monthly subscription. Once payment is confirmed, full navigation and functions are automatically unlocked.</p>

                @if(!empty($billingData['checkout_error']))
                    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        {{ $billingData['checkout_error'] }}
                    </div>
                @endif

                @if(!empty($billingData['checkout_qr_url']))
                    <div class="mt-5 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                        <img src="{{ $billingData['checkout_qr_url'] }}" alt="Stripe Checkout QR" class="h-56 w-56 rounded-2xl border border-amber-200 bg-white p-2">
                        <div class="space-y-3">
                            <p class="text-sm text-amber-800">If your phone cannot scan, use this button:</p>
                            <a href="{{ $billingData['checkout_url'] }}" class="inline-flex rounded-3xl bg-amber-500 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-400">Open Stripe Checkout</a>
                        </div>
                    </div>
                @endif

                @if(!empty($billingData['dummy_qr_url']))
                    <div class="mt-6 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                        <h3 class="text-sm font-semibold text-sky-800">Sandbox Dummy QR (Local Test Only)</h3>
                        <p class="mt-1 text-xs text-sky-700">Use this QR to simulate payment and unlock navigation instantly in local testing.</p>
                        <div class="mt-3 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                            <img src="{{ $billingData['dummy_qr_url'] }}" alt="Dummy Activation QR" class="h-40 w-40 rounded-xl border border-sky-200 bg-white p-2">
                            <a href="{{ $billingData['dummy_activate_url'] }}" class="inline-flex rounded-3xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-400">Simulate Payment Activation</a>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Current Plan</h2>
            <p class="mt-2 text-sm text-slate-500">Plan: <span class="font-semibold">Special PM Monthly</span></p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status</dt>
                    <dd class="mt-2 text-base font-semibold text-slate-900">{{ strtoupper($billingData['status'] ?? 'INACTIVE') }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Current Period Ends</dt>
                    <dd class="mt-2 text-base font-semibold text-slate-900">{{ $billingData['current_period_end'] ? \Carbon\Carbon::parse($billingData['current_period_end'])->format('M d, Y h:i A') : 'Not available' }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Stripe Customer</dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $billingData['customer_id'] ?: 'Not created yet' }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Stripe Subscription</dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $billingData['subscription_id'] ?: 'No active subscription' }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('special-pm.billing.checkout') }}">
                    @csrf
                    <button type="submit" class="rounded-3xl bg-emerald-500 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">Start / Manage Monthly Subscription</button>
                </form>

                <form method="POST" action="{{ route('special-pm.billing.refresh') }}">
                    @csrf
                    <button type="submit" class="rounded-3xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Refresh Subscription Status</button>
                </form>

                <form method="POST" action="{{ route('special-pm.billing.deactivate') }}" onsubmit="return confirm('Set subscription status to INACTIVE and lock full dashboard access?');">
                    @csrf
                    <button type="submit" class="rounded-3xl bg-rose-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-rose-400">Deactivate Status</button>
                </form>
            </div>

            <p class="mt-6 text-xs text-slate-500">
                Price ID in use: {{ $billingData['price_id'] ?: 'Missing STRIPE_SPECIAL_PM_PRICE_ID in .env' }}
            </p>
        </section>
    </div>
</div>
@endsection
