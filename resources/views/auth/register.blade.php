<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Registration | PCMS</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    @vite(['resources/js/app.jsx'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="mx-auto flex min-h-screen w-full max-w-6xl items-start px-4 py-6 sm:items-center sm:px-6 sm:py-10 lg:px-8">
        <div class="w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl sm:rounded-[2rem]">
            <div class="border-b border-slate-200 bg-slate-950 px-5 py-7 text-white sm:px-10 sm:py-8">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">PCMS Invite</p>
                <h1 class="mt-3 text-2xl font-semibold sm:text-3xl">Complete your account setup</h1>
                <p class="mt-2 text-sm text-slate-300">Finish your profile to start collaborating in the project dashboard.</p>
            </div>

            <div class="px-5 py-7 sm:px-10 sm:py-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2" autocomplete="off">
                    @csrf
                    {{-- Honeypot fields to trick browser autofill away from real fields --}}
                    <input type="text" name="fake_username" style="display:none;" tabindex="-1" autocomplete="username">
                    <input type="password" name="fake_password" style="display:none;" tabindex="-1" autocomplete="current-password">

                    <input type="hidden" name="role" value="{{ old('role', request('role', 'client')) }}">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" placeholder="johndoe" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" placeholder="John" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" placeholder="Doe" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Position</label>
                        <input type="text" name="position" value="{{ old('position') }}" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" placeholder="Project Manager" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Company</label>
                        <input type="text" name="company" value="{{ old('company', request('company')) }}" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" placeholder="Your company" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', request('email')) }}" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" placeholder="you@company.com" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" required>
                            <button type="button" onclick="togglePassword('password', 'eye-password')" class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-600 focus:outline-none" tabindex="-1" aria-label="Toggle password visibility">
                                <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50" required>
                            <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm')" class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-600 focus:outline-none" tabindex="-1" aria-label="Toggle confirm password visibility">
                                <svg id="eye-confirm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2 md:col-span-2">
                        <button type="submit" class="w-full rounded-2xl bg-brand-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-600">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($alreadyRegistered ?? false)
    {{-- Already-registered overlay: shown when the invited user has already completed setup --}}
    <div id="already-registered-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="mx-4 w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="bg-slate-950 px-6 py-6 text-center text-white">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold">Account Already Created</h2>
            </div>
            <div class="px-6 py-7 text-center">
                <p class="text-sm text-slate-600">
                    This invitation link has already been used to create an account.
                    Please contact the administrator if you need something to be edited.
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ config('app.url') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-2xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        function togglePassword(inputId, iconId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        }
    </script>
</body>
</html>