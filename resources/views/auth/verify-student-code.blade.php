<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Student Account - Fiat Lux Academe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f7fb] p-4">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
        <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="mb-6 h-16 w-16 rounded-full border-2 border-[#ffd22d] object-contain">
        <h1 class="text-2xl font-bold text-[#000638]">Check your Gmail</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Enter the six-digit verification code. It expires after 10 minutes and is locked after five incorrect attempts.
        </p>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('student.registration.verify') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="space-y-2">
                <label for="code" class="text-sm font-medium text-slate-700">Verification code</label>
                <input id="code" type="text" name="code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-2xl font-bold tracking-[0.45em] outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="000000">
            </div>
            <button type="submit" class="w-full rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white hover:bg-[#10175a]">
                Verify and create account
            </button>
        </form>

        <a href="{{ route('student.registration.request') }}" class="mt-6 block text-center text-sm font-semibold text-[#000638] hover:underline">
            Start again
        </a>
    </main>
</body>
</html>
