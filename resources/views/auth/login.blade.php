<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $portal['title'] }} - {{ \App\Support\SystemContent::schoolName() }} Document Request Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.responsive-foundation')
    @if (config('services.turnstile.enabled'))
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
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .login-photo {
            background-image: url('{{ asset('images/FLAfrontview.jpg') }}');
            background-position: center 42%;
            background-repeat: no-repeat;
            background-size: cover;
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            .login-photo { background-position: center 38%; }
        }
        @media (max-width: 767px) {
            .login-photo { background-position: center 36%; }
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center overflow-x-hidden bg-[#f5f7fb] p-3 sm:p-5 lg:h-screen lg:p-5">
    <main class="grid w-full max-w-6xl overflow-hidden rounded-2xl border border-slate-200 bg-[#f1f4fa] shadow-[0_18px_55px_rgba(15,23,42,0.12)] md:grid-cols-[1fr_1fr] lg:min-h-[595px] lg:grid-cols-[1.08fr_1fr]">
        <section class="login-photo relative min-h-[190px] overflow-hidden bg-[#000638] sm:min-h-[220px] md:min-h-[640px] lg:min-h-0" aria-label="Fiat Lux Academe campus">
            <div class="absolute inset-0 bg-gradient-to-t from-[#000638]/90 via-[#000638]/42 to-[#000638]/10"></div>
            <div class="absolute inset-x-0 top-0 h-1 bg-[#ffd22d]"></div>

            <div class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-7 lg:p-8">
                <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                    <img src="{{ asset('images/FLAOFFICIAL_LOGO.png') }}" alt="Fiat Lux Academe logo" class="h-16 w-16 shrink-0 object-contain sm:h-20 sm:w-20">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold tracking-[0.13em] text-white sm:text-base sm:tracking-[0.16em]">FIAT LUX ACADEME</p>
                        <p class="mt-0 text-xs font-medium tracking-wide text-[#ffd22d] sm:text-sm">Cavite</p>
                    </div>
                </div>

                <h1 class="mt-3 text-2xl font-semibold leading-tight sm:text-3xl">Document Request Hub</h1>
                <p class="mt-2 text-sm leading-6 text-slate-100 sm:mt-4 sm:text-base">Access official document requests, status tracking, and academic records in one secure portal.</p>
            </div>
        </section>

        <section class="relative flex min-w-0 items-center bg-[#f6f8ff] px-5 py-6 sm:px-7 md:px-8 md:py-6 lg:px-9 lg:py-5">
            <div class="mx-auto w-full max-w-md">
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $portal['title'] }}</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-[#000638]">Welcome back</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    @if ($accountType === 'student')
                        Sign in to your student account.
                    @elseif ($accountType === 'admin')
                        Sign in to your administrator account.
                    @else
                        Sign in to your staff account.
                    @endif
                </p>
            </div>

            <form action="{{ $portal['form_action'] }}" method="POST" class="w-full space-y-2.5">
                @csrf
                <input type="hidden" name="form_started_at" value="{{ $formStartedAt }}">
                <div class="absolute -left-[10000px] h-px w-px overflow-hidden" aria-hidden="true">
                    <label for="website">Leave this field empty</label>
                    <input id="website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                </div>

                @if (request()->boolean('session_expired'))
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Your session was refreshed. Please sign in again.
                    </div>
                @endif

                @if (session('status'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-2">
                    <label for="login-identifier" class="text-sm font-medium text-slate-700">{{ $portal['identifier_label'] }}</label>
                    <input id="login-identifier" type="{{ $portal['identifier_type'] }}" name="identifier" value="{{ old('identifier') }}" required maxlength="{{ $accountType === 'student' ? 50 : 255 }}" autofocus
                        autocomplete="username"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#000638] focus:ring-2 focus:ring-[#000638]/15"
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
                            class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-28 text-base text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#000638] focus:ring-2 focus:ring-[#000638]/15"
                            placeholder="Password">
                        <button id="password-toggle" type="button" aria-controls="password" aria-pressed="false" aria-label="Show password"
                            class="absolute inset-y-0 right-0 flex min-w-20 items-center justify-center gap-1.5 rounded-r-xl px-3 text-sm font-semibold text-[#000638] transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#ffd22d]">
                            <svg id="password-eye-icon" aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <span id="password-toggle-label">Show</span>
                        </button>
                    </div>
                </div>

                @if ($accountType !== 'admin')
                    <label class="flex min-h-6 items-center gap-2.5 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-[#000638] focus:ring-[#ffd22d]">
                        Remember me
                    </label>
                @endif

                @if (config('services.turnstile.enabled'))
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

                <button id="turnstile-start" type="button" class="w-full rounded-xl border border-slate-300 bg-white py-3 text-sm font-semibold text-[#000638] transition hover:border-slate-400 hover:bg-slate-50 active:bg-slate-100">
                    Verify you are human
                </button>
                @endif

                <button id="login-submit" type="submit" @if (config('services.turnstile.enabled')) disabled @endif class="w-full rounded-xl bg-[#000638] py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#10175a] active:translate-y-px active:bg-[#000426] @if (config('services.turnstile.enabled')) cursor-not-allowed opacity-50 @endif">
                    Sign in
                </button>
                @if ($accountType === 'student')
                    <p class="pt-1 text-center text-sm text-slate-600">
                        No account yet?
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

                    if (turnstileButton && turnstileStatus) {
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
                    }
                })();
            </script>
            </div>
        </section>
    </main>

    @include('partials.loading-overlay')
</body>
</html>
