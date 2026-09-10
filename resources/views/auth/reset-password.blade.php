<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Fiat Lux Academe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f7fb] p-4">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
        <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="mb-6 h-16 w-16 rounded-full border-2 border-[#ffd22d] object-contain">
        <h1 class="text-2xl font-bold text-[#000638]">Create a new password</h1>
        <p class="mt-2 text-sm text-slate-600">Create a strong password that meets every requirement below.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-slate-700">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
            </div>
            <div class="space-y-2">
                <label for="password" class="text-sm font-medium text-slate-700">New password</label>
                <input id="password" type="password" name="password" required minlength="12" maxlength="64" autocomplete="new-password"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
                @include('auth.partials.password-requirements')
            </div>
            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" maxlength="64" autocomplete="new-password"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
            </div>
            <button type="submit" class="w-full rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white hover:bg-[#10175a]">
                Reset password
            </button>
        </form>
    </main>
</body>
</html>
