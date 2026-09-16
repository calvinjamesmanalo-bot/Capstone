<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Fiat Lux Academe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.responsive-foundation')
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f7fb] p-4">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
        <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="mb-6 h-16 w-16 rounded-full border-2 border-[#ffd22d] object-contain">
        <h1 class="text-2xl font-bold text-[#000638]">Forgot your password?</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Enter your account email and we’ll send password reset instructions if the account exists.</p>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="note">
                <p class="font-semibold">Didn’t receive a reset email?</p>
                <p class="mt-1">
                    The address may not be registered, may not be verified, or may not match the official student roster.
                    For your security, this page cannot confirm which condition applies. Check your Spam folder, then contact the registrar for account verification.
                </p>
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-slate-700">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
                @error('email')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white hover:bg-[#10175a]">
                Send reset link
            </button>
        </form>

        <div class="mt-6 text-center text-sm font-semibold text-[#000638]">
            <a href="{{ route('login') }}" class="hover:underline">Back to sign in</a>
        </div>
    </main>
</body>
</html>
