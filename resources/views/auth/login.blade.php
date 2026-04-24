<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PCMS</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    @vite(['resources/js/app.jsx'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">

    <div class="flex min-h-screen">

        {{-- Left panel --}}
        <div class="relative hidden flex-col justify-between bg-slate-950 px-10 py-12 text-white lg:flex lg:w-[46%]">
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white p-1">
                        <img src="/images/sgpro-logo.webp" alt="SGpro Logo" class="h-full w-full object-contain">
                    </div>
                    <span class="text-sm font-semibold tracking-wide text-slate-300">SGpro.co PCMS Portal</span>
                </div>

                <h1 class="mt-14 text-4xl font-bold leading-tight">
                    Welcome back.<br>
                    <span class="text-brand-400">Let's get to work.</span>
                </h1>
                <p class="mt-5 max-w-sm text-base text-slate-400">
                    Sign in to continue managing projects, teams, and client deliverables.
                </p>

                <div class="mt-10 space-y-4">
                    <div class="flex items-start gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-4">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-500/20 text-brand-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Security</p>
                            <p class="mt-1 text-sm text-slate-300">Role-based access — everyone lands on the right dashboard.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-4">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-400/20 text-brand-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Productivity</p>
                            <p class="mt-1 text-sm text-slate-300">Track tasks, timelines and project completion in one workspace.</p>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-xs text-slate-600">&copy; {{ date('Y') }} Project Coordination System</p>
        </div>

        {{-- Right panel — login form --}}
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-8 sm:py-12">
            <div class="w-full max-w-md">

                {{-- Mobile brand --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white p-1">
                        <img src="/images/sgpro-logo.webp" alt="SGpro Logo" class="h-full w-full object-contain">
                    </div>
                    <span class="text-sm font-semibold tracking-wide text-slate-500">SGpro.co PCMS Portal</span>
                </div>

                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Account Access</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Sign in</h2>
                <p class="mt-2 text-sm text-slate-500">Enter your credentials to access your dashboard.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" autocomplete="off">
                    @csrf

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50"
                            placeholder="you@company.com" required autofocus>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-semibold text-slate-700">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs text-brand-500 hover:underline">Forgot password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <input type="password" id="login_password" name="password" autocomplete="current-password"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-50"
                                placeholder="Enter your password" required>
                            <button type="button" onclick="togglePassword('login_password', 'eye-login-password')" class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-600 focus:outline-none" aria-label="Toggle password visibility">
                                <svg id="eye-login-password" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-slate-300 accent-brand-500">
                        <label for="remember" class="text-sm text-slate-600">Remember me</label>
                    </div>

                    <button type="submit"
                        class="w-full rounded-2xl bg-brand-500 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#dbe2ff] transition hover:bg-brand-600">
                        Sign in
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Contact <span class="font-semibold text-brand-500">SGpro Project Manager</span>
                </p>

            </div>
        </div>

    </div>

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