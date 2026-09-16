<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Fiat Lux Academe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.responsive-foundation')
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f7fb] p-4">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
        <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="mb-6 h-16 w-16 rounded-full border-2 border-[#ffd22d] object-contain">
        <h1 class="text-2xl font-bold text-[#000638]">Verify your email</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Open that link before using your student account.
        </p>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <form action="{{ route('verification.send') }}" method="POST" class="mt-6">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white hover:bg-[#10175a]">Resend verification email</button>
        </form>

        @if (app()->environment(['local', 'testing']))
            <form action="{{ route('verification.skip') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-amber-300 bg-amber-50 py-3 text-sm font-semibold text-amber-800 hover:bg-amber-100">Skip for testing</button>
            </form>
        @endif

        <form action="{{ route('logout') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="w-full rounded-lg border border-slate-300 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign out</button>
        </form>
    </main>
</body>
</html>
