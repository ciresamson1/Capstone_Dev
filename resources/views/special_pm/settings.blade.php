@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-slate-100 p-6 xl:p-8">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Special PM SaaS</p>
            <h1 class="text-2xl font-semibold text-slate-900">White Label Settings</h1>
            <p class="mt-1 text-sm text-slate-500">Control your tenant branding and company profile.</p>
        </div>

        @include('special_pm._nav')

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-700">{{ session('status') }}</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('special-pm.settings.update') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')

                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Brand Name</label>
                    <input type="text" name="white_label_brand_name" value="{{ old('white_label_brand_name', $user->white_label_brand_name) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" placeholder="Your agency brand">
                    @error('white_label_brand_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Company</label>
                    <input type="text" name="company" value="{{ old('company', $user->company) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" required>
                    @error('company')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Logo URL</label>
                    <input type="url" name="white_label_logo_url" value="{{ old('white_label_logo_url', $user->white_label_logo_url) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" placeholder="https://your-domain.com/logo.png">
                    @error('white_label_logo_url')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Primary Color</label>
                    <input type="text" name="white_label_primary_color" value="{{ old('white_label_primary_color', $user->white_label_primary_color) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" placeholder="#0f172a" required>
                    @error('white_label_primary_color')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Accent Color</label>
                    <input type="text" name="white_label_accent_color" value="{{ old('white_label_accent_color', $user->white_label_accent_color) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" placeholder="#38bdf8" required>
                    @error('white_label_accent_color')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-3xl bg-sky-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-sky-400">Save White Label Settings</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
