@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-slate-100 p-6 xl:p-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Special PM SaaS</p>
            <h1 class="text-2xl font-semibold text-slate-900">Manage DM</h1>
            <p class="mt-1 text-sm text-slate-500">Invite and remove Digital Marketers under {{ $user->company ?: 'your company' }}.</p>
        </div>

        @include('special_pm._nav')

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-700">{{ session('status') }}</div>
        @endif

        <section class="mb-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Invite New DM</h2>
            <p class="mt-1 text-sm text-slate-500">An invite email will be sent with DM role pre-selected.</p>
            <form method="POST" action="{{ route('special-pm.manage-dm.invite') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="w-full">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">DM Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" required>
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-3xl bg-emerald-500 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">Send DM Invite</button>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Current DMs</h2>
            <p class="mt-1 text-sm text-slate-500">Digital Marketers in your tenant.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm text-slate-600">
                    <thead>
                        <tr>
                            <th class="pb-3 pr-6 font-semibold text-slate-900">Name</th>
                            <th class="pb-3 pr-6 font-semibold text-slate-900">Email</th>
                            <th class="pb-3 pr-6 font-semibold text-slate-900">Company</th>
                            <th class="pb-3 pr-6 font-semibold text-slate-900">Joined</th>
                            <th class="pb-3 font-semibold text-slate-900">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($dms as $dm)
                            <tr>
                                <td class="py-3 pr-6 font-semibold text-slate-900">{{ $dm->name }}</td>
                                <td class="py-3 pr-6">{{ $dm->email }}</td>
                                <td class="py-3 pr-6">{{ $dm->company ?: '—' }}</td>
                                <td class="py-3 pr-6">{{ optional($dm->created_at)->format('M d, Y') ?: '—' }}</td>
                                <td class="py-3">
                                    <form method="POST" action="{{ route('special-pm.manage-dm.destroy', $dm->id) }}" onsubmit="return confirm('Remove this DM user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-2xl bg-rose-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-400">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500">No DM users found for your company yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
