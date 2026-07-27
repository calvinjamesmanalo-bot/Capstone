<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fiat Lux Academe Document Request Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <main class="w-full max-w-7xl min-h-[min(760px,calc(100vh-3rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg grid lg:grid-cols-[1.15fr_0.85fr]">
        <section class="relative hidden min-h-[680px] overflow-hidden bg-[#000638] lg:block">
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

        <section class="relative flex flex-col justify-center p-7 md:p-10 lg:p-12">
            <div class="mb-7">
                <div class="mb-5 flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-[#ffd22d] bg-white shadow-sm">
                    <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="h-full w-full object-contain">
                </div>
                <p class="text-sm font-medium text-slate-500">
                    {{ \App\Models\Setting::where('key', 'institution_name')->first()->value ?? 'Fiat Lux Academe' }}
                </p>
                <h2 class="mt-2 text-2xl font-bold text-[#000638]">Welcome back</h2>
                <p class="mt-2 text-sm text-slate-600">Use your school account to continue.</p>
            </div>

            <form action="{{ route('login') }}" method="POST" class="w-full space-y-5">
                @csrf

                @if (request()->boolean('session_expired'))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Your session was refreshed. Please sign in again.
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
                    <label for="email" class="text-sm font-medium text-slate-700">Username or student ID</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-base text-slate-700 outline-none transition focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                        placeholder="Email or Student Number">
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                    <input id="password" type="password" name="password" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-base text-slate-700 outline-none transition focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                        placeholder="Password">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#000638] focus:ring-[#ffd22d]">
                    Remember me
                </label>

                <button type="submit" class="w-full rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white transition hover:bg-[#10175a]">
                    Sign in
                </button>
            </form>

            <div class="mt-10 border-t border-slate-200 pt-6">
                <p class="mb-3 text-xs font-medium text-slate-500">Quick access for testing</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('login.as', 'admin') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Admin</a>
                    <a href="{{ route('login.as', 'student') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Student</a>
                    <a href="{{ route('login.as', 'registrar') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Registrar</a>
                    <a href="{{ route('login.as', 'records_officer') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Records</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
