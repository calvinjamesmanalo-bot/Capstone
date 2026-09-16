<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $portal['title'] }} - Fiat Lux Academe Document Request Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.responsive-foundation')
    <script>
        window.onTurnstileSuccess = () => {
            const status = document.getElementById('turnstile-status');
            const verificationButton = document.getElementById('turnstile-start');
            const submit = document.getElementById('login-submit');
            if (status) {
                status.textContent = 'Security check completed.';
                status.className = 'mt-2 text-center text-xs font-medium text-emerald-700';
            }
            if (verificationButton) {
                verificationButton.disabled = true;
                verificationButton.textContent = 'Human verification completed';
                verificationButton.classList.add('cursor-not-allowed', 'border-emerald-300', 'bg-emerald-50', 'text-emerald-700');
                verificationButton.classList.remove('border-slate-300', 'bg-white', 'text-[#000638]', 'hover:bg-slate-50');
            }
            if (submit) {
                submit.disabled = false;
                submit.classList.remove('cursor-not-allowed', 'opacity-50');
            }
        };

        window.onTurnstileError = (errorCode) => {
            const status = document.getElementById('turnstile-status');
            const verificationButton = document.getElementById('turnstile-start');
            const submit = document.getElementById('login-submit');
            if (status) {
                status.textContent = `Security check could not load (code ${errorCode}). Refresh the page or check the configured hostname.`;
                status.className = 'mt-2 text-center text-xs font-medium text-red-700';
            }
            if (verificationButton) {
                verificationButton.disabled = false;
                verificationButton.textContent = 'Try human verification again';
                verificationButton.classList.add('border-slate-300', 'bg-white', 'text-[#000638]', 'hover:bg-slate-50');
                verificationButton.classList.remove('cursor-not-allowed', 'border-emerald-300', 'bg-emerald-50', 'text-emerald-700');
            }
            if (submit) {
                submit.disabled = true;
                submit.classList.add('cursor-not-allowed', 'opacity-50');
            }
            return true;
        };

        window.onTurnstileExpired = () => window.onTurnstileError('expired');
        window.onTurnstileTimeout = () => window.onTurnstileError('timeout');
        window.onTurnstileUnsupported = () => window.onTurnstileError('unsupported-browser');
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .campus-image {
            width: 112%;
            max-width: none;
            animation: campus-slide 18s ease-in-out infinite alternate;
            transform-origin: center;
        }
        @keyframes campus-slide {
            from { transform: translateX(-5%) scale(1.04); }
            to { transform: translateX(5%) scale(1.04); }
        }
        @media (prefers-reduced-motion: reduce) {
            .campus-image { animation: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#f6f7fb] flex items-center justify-center p-4 pb-28 lg:h-screen lg:overflow-hidden lg:p-4">
    <main class="grid w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg lg:h-[min(680px,calc(100vh-2rem))] lg:grid-cols-[1fr_0.9fr]">
        <section class="relative hidden overflow-hidden bg-[#000638] lg:block">
            <img src="{{ asset('images/login.jpg') }}" alt="Fiat Lux Academe campus" class="campus-image absolute inset-y-0 -left-[6%] h-full object-cover">
            <div class="absolute inset-0 bg-[#000638]/55"></div>
            <div class="absolute inset-x-0 top-0 h-1.5 bg-[#ffd22d]"></div>

            <div class="absolute inset-x-0 bottom-0 p-8 text-white">
                <div class="mb-4 flex items-center gap-3">
                    <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="h-14 w-14 rounded-full bg-white ring-2 ring-[#ffd22d]">
                    <div>
                        <p class="text-sm font-bold">FIAT LUX ACADEME</p>
                        <p class="mt-1 text-xs text-[#ffd22d]">Cavite</p>
                    </div>
                </div>
                <h1 class="text-3xl font-semibold leading-tight">Document Request Hub</h1>
                <p class="mt-3 max-w-lg text-sm leading-6 text-slate-200">
                    Access official document requests, status tracking, and academic records in one secure portal.
                </p>
            </div>
        </section>

        <section class="relative flex flex-col justify-start overflow-hidden p-5 md:p-6">
            <div class="mb-3 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-[#ffd22d] bg-white shadow-sm">
                        <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="h-full w-full object-contain">
                    </div>
                    <p class="text-sm font-semibold text-slate-500">
                        {{ \App\Models\Setting::where('key', 'institution_name')->first()->value ?? 'Fiat Lux Academe' }}
                    </p>
                </div>
                <h2 class="mt-2 text-2xl font-bold text-[#000638]">{{ $portal['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $portal['description'] }}</p>
            </div>

            <form action="{{ $portal['form_action'] }}" method="POST" class="w-full space-y-3">
                @csrf
                <input type="hidden" name="form_started_at" value="{{ $formStartedAt }}">
                <div class="absolute -left-[10000px] h-px w-px overflow-hidden" aria-hidden="true">
                    <label for="website">Leave this field empty</label>
                    <input id="website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                </div>

                @if (request()->boolean('session_expired'))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Your session was refreshed. Please sign in again.
                    </div>
                @endif

                @if (session('status'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-2">
                    <label for="login-identifier" class="text-sm font-medium text-slate-700">{{ $portal['identifier_label'] }}</label>
                    <input id="login-identifier" type="{{ $portal['identifier_type'] }}" name="identifier" value="{{ old('identifier') }}" required maxlength="{{ $accountType === 'student' ? 50 : 255 }}" autofocus
                        autocomplete="username"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-base text-slate-700 outline-none transition focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                        placeholder="{{ $portal['identifier_placeholder'] }}">
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-4">
                        <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#000638] hover:underline">
                            Forgot password?
                        </a>
                    </div>
                    <div class="relative">
                        <input id="password" type="password" name="password" required maxlength="1024"
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-4 pr-32 text-base text-slate-700 outline-none transition focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                            placeholder="Password">
                        <button id="password-toggle" type="button" aria-controls="password" aria-pressed="false" aria-label="Show password"
                            class="absolute inset-y-0 right-0 flex items-center gap-1.5 rounded-r-lg px-4 text-sm font-semibold text-[#000638] hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#ffd22d]">
                            <svg id="password-eye-icon" aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <span id="password-toggle-label">Show</span>
                        </button>
                    </div>
                </div>

                @if ($accountType !== 'admin')
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#000638] focus:ring-[#ffd22d]">
                        Remember me
                    </label>
                @endif

                <div class="flex min-h-[65px] w-full items-center justify-center overflow-hidden">
                    <div id="turnstile-widget" class="cf-turnstile w-full"
                        data-sitekey="{{ config('services.turnstile.site_key') }}"
                        data-action="{{ config('services.turnstile.expected_action') }}"
                        data-size="flexible"
                        data-theme="light"
                        data-execution="execute"
                        data-appearance="execute"
                        data-retry="never"
                        data-refresh-expired="manual"
                        data-refresh-timeout="manual"
                        data-callback="onTurnstileSuccess"
                        data-error-callback="onTurnstileError"
                        data-expired-callback="onTurnstileExpired"
                        data-timeout-callback="onTurnstileTimeout"
                        data-unsupported-callback="onTurnstileUnsupported"></div>
                </div>
                <p id="turnstile-status" class="text-center text-xs font-medium text-slate-500" role="status">
                    Human verification will only run when you start it.
                </p>

                <button id="turnstile-start" type="button" class="w-full rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-semibold text-[#000638] transition hover:bg-slate-50">
                    Verify you are human
                </button>

                <button id="login-submit" type="submit" disabled class="w-full cursor-not-allowed rounded-lg bg-[#000638] py-2.5 text-sm font-semibold text-white opacity-50 transition hover:bg-[#10175a]">
                    Sign in
                </button>
                @if ($accountType === 'student')
                    <p class="text-center text-sm text-slate-600">
                        New student?
                        <a href="{{ route('student.registration.request') }}" class="font-semibold text-[#000638] hover:underline">
                            Create Student Account
                        </a>
                    </p>
                @endif
            </form>

            <script>
                (() => {
                    const passwordInput = document.getElementById('password');
                    const passwordToggle = document.getElementById('password-toggle');
                    const passwordToggleLabel = document.getElementById('password-toggle-label');
                    const turnstileButton = document.getElementById('turnstile-start');
                    const turnstileStatus = document.getElementById('turnstile-status');
                    let turnstileHasRun = false;

                    passwordToggle.addEventListener('click', () => {
                        const isVisible = passwordInput.type === 'text';
                        passwordInput.type = isVisible ? 'password' : 'text';
                        passwordToggleLabel.textContent = isVisible ? 'Show' : 'Hide';
                        passwordToggle.setAttribute('aria-pressed', String(!isVisible));
                        passwordToggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                    });

                    turnstileButton.addEventListener('click', () => {
                        if (!window.turnstile) {
                            turnstileStatus.textContent = 'The security check is still loading. Please try again.';
                            return;
                        }

                        turnstileButton.disabled = true;
                        turnstileButton.classList.add('cursor-not-allowed');
                        turnstileStatus.textContent = 'Checking your browser…';
                        turnstileStatus.className = 'mt-2 text-center text-xs font-medium text-slate-500';

                        try {
                            if (turnstileHasRun) {
                                window.turnstile.reset('#turnstile-widget');
                            }

                            turnstileHasRun = true;
                            window.turnstile.execute('#turnstile-widget');
                        } catch (error) {
                            window.onTurnstileError('not-ready');
                        }
                    });
                })();
            </script>

        </section>
    </main>

    @if (app()->environment(['local', 'testing']))
        <aside id="quick-access-panel" aria-label="Quick access for testing"
            class="fixed bottom-3 left-1/2 z-30 w-[calc(100%-1.5rem)] max-w-lg -translate-x-1/2 rounded-lg border border-slate-200 bg-white/95 p-2 shadow-lg backdrop-blur lg:bottom-auto lg:left-4 lg:top-4 lg:w-auto lg:max-w-none lg:translate-x-0">
            <div class="flex flex-wrap items-center justify-center gap-1.5">
                <p class="mr-1 text-[11px] font-semibold text-slate-500">Quick access for testing</p>
                <a href="{{ route('login.as', 'student') }}" class="rounded-md bg-slate-100 px-2 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-200">Student</a>
                <a href="{{ route('login.as', 'registrar') }}" class="rounded-md bg-slate-100 px-2 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-200">Registrar</a>
                <a href="{{ route('login.as', 'records_officer') }}" class="rounded-md bg-slate-100 px-2 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-200">Records</a>
                <a href="{{ route('login.as', 'admin') }}" class="rounded-md bg-slate-100 px-2 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-200">Admin</a>
            </div>
        </aside>
    @endif

    @include('partials.loading-overlay')
</body>
</html>
