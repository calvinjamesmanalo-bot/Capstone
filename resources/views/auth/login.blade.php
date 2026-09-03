<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fiat Lux Academe Document Request Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.onTurnstileSuccess = () => {
            const status = document.getElementById('turnstile-status');
            const submit = document.getElementById('login-submit');
            if (status) {
                status.textContent = 'Security check completed.';
                status.className = 'mt-2 text-center text-xs font-medium text-emerald-700';
            }
            if (submit) {
                submit.disabled = false;
                submit.classList.remove('cursor-not-allowed', 'opacity-50');
            }
        };

        window.onTurnstileError = (errorCode) => {
            const status = document.getElementById('turnstile-status');
            const submit = document.getElementById('login-submit');
            if (status) {
                status.textContent = `Security check could not load (code ${errorCode}). Refresh the page or check the configured hostname.`;
                status.className = 'mt-2 text-center text-xs font-medium text-red-700';
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
<body class="min-h-screen bg-[#f6f7fb] flex items-center justify-center p-4 lg:p-6">
    <main class="grid w-full max-w-6xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg lg:h-[min(680px,calc(100vh-3rem))] lg:grid-cols-[1.1fr_0.9fr]">
        <section class="relative hidden overflow-hidden bg-[#000638] lg:block">
            <img src="{{ asset('images/login.jpg') }}" alt="Fiat Lux Academe campus" class="campus-image absolute inset-y-0 -left-[6%] h-full object-cover">
            <div class="absolute inset-0 bg-[#000638]/55"></div>
            <div class="absolute inset-x-0 top-0 h-1.5 bg-[#ffd22d]"></div>

            <div class="absolute inset-x-0 bottom-0 p-10 text-white">
                <div class="mb-6 flex items-center gap-4">
                    <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="h-16 w-16 rounded-full bg-white ring-2 ring-[#ffd22d]">
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

        <section class="relative flex flex-col justify-start overflow-y-auto p-6 md:p-8 lg:p-9">
            <div class="mb-5 shrink-0">
                <div class="mb-4 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-[#ffd22d] bg-white shadow-sm">
                    <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="h-full w-full object-contain">
                </div>
                <p class="text-sm font-medium text-slate-500">
                    {{ \App\Models\Setting::where('key', 'institution_name')->first()->value ?? 'Fiat Lux Academe' }}
                </p>
                <h2 class="mt-2 text-2xl font-bold text-[#000638]">Welcome back</h2>
                <p class="mt-2 text-sm text-slate-600">Use your school account to continue.</p>
            </div>

            <form action="{{ route('login') }}" method="POST" class="w-full space-y-4">
                @csrf

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

                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-slate-700">Sign in as</legend>
                    <div class="grid grid-cols-3 gap-2" id="account-type-selector">
                        @foreach ([
                            'student' => 'Student',
                            'staff' => 'Staff',
                            'admin' => 'Admin',
                        ] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="account_type" value="{{ $value }}" class="peer sr-only"
                                    @checked(old('account_type', 'student') === $value)>
                                <span class="block rounded-lg border border-slate-300 px-3 py-2.5 text-center text-sm font-semibold text-slate-600 transition peer-checked:border-[#000638] peer-checked:bg-[#000638] peer-checked:text-white">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('account_type') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                <div class="space-y-2">
                    <label id="login-identifier-label" for="login-identifier" class="text-sm font-medium text-slate-700">Student Number</label>
                    <input id="login-identifier" type="text" name="identifier" value="{{ old('identifier') }}" required maxlength="50" autofocus
                        autocomplete="username"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-base text-slate-700 outline-none transition focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                        placeholder="e.g. 2023-1234">
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                    <input id="password" type="password" name="password" required maxlength="1024"
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-base text-slate-700 outline-none transition focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                        placeholder="Password">
                    <div class="text-right">
                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#000638] hover:underline">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <label id="remember-me-field" class="flex items-center gap-2 text-sm text-slate-600 {{ old('account_type', 'student') === 'admin' ? 'hidden' : '' }}">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#000638] focus:ring-[#ffd22d]">
                    Remember me
                </label>

                <div class="flex min-h-[65px] w-full items-center justify-center overflow-hidden">
                    <div class="cf-turnstile w-full"
                        data-sitekey="{{ config('services.turnstile.site_key') }}"
                        data-action="{{ config('services.turnstile.expected_action') }}"
                        data-size="flexible"
                        data-theme="light"
                        data-callback="onTurnstileSuccess"
                        data-error-callback="onTurnstileError"
                        data-expired-callback="onTurnstileExpired"
                        data-timeout-callback="onTurnstileTimeout"
                        data-unsupported-callback="onTurnstileUnsupported"></div>
                </div>
                <p id="turnstile-status" class="text-center text-xs font-medium text-slate-500" role="status">
                    Loading security check…
                </p>

                <button id="login-submit" type="submit" disabled class="w-full cursor-not-allowed rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white opacity-50 transition hover:bg-[#10175a]">
                    Sign in
                </button>
                <p class="text-center text-sm text-slate-600">
                    New student?
                    <a href="{{ route('student.registration.request') }}" class="font-semibold text-[#000638] hover:underline">
                        Create Student Account
                    </a>
                </p>
            </form>

            <script>
                (() => {
                    const choices = document.querySelectorAll('input[name="account_type"]');
                    const rememberField = document.getElementById('remember-me-field');
                    const identifierLabel = document.getElementById('login-identifier-label');
                    const identifierInput = document.getElementById('login-identifier');

                    const updateAccountType = () => {
                        const selected = document.querySelector('input[name="account_type"]:checked')?.value;
                        const isStudent = selected === 'student';
                        const isAdmin = selected === 'admin';

                        rememberField.classList.toggle('hidden', isAdmin);
                        identifierLabel.textContent = isStudent ? 'Student Number' : 'Email address';
                        identifierInput.type = isStudent ? 'text' : 'email';
                        identifierInput.maxLength = isStudent ? 50 : 255;
                        identifierInput.placeholder = isStudent ? 'e.g. 2023-1234' : 'you@example.com';
                    };

                    choices.forEach((choice) => choice.addEventListener('change', updateAccountType));
                    updateAccountType();
                })();
            </script>

            @if (app()->environment(['local', 'testing']))
                <div class="mt-6 border-t border-slate-200 pt-4">
                    <p class="mb-3 text-xs font-medium text-slate-500">Quick access for testing</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('login.as', 'student') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Student</a>
                        <a href="{{ route('login.as', 'registrar') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Registrar</a>
                        <a href="{{ route('login.as', 'records_officer') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Records</a>
                        <a href="{{ route('login.as', 'admin') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Admin</a>
                    </div>
                </div>
            @endif
        </section>
    </main>
    @include('partials.loading-overlay')
</body>
</html>
