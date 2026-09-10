<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student Account - Fiat Lux Academe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f7fb] p-4">
    <main class="my-6 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
        <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="mb-6 h-16 w-16 rounded-full border-2 border-[#ffd22d] object-contain">
        <h1 class="text-2xl font-bold text-[#000638]">Create Student Account</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Enter your student details and a Gmail address that you can access. Your account becomes active only after you enter the emailed code.
        </p>

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('student.registration.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <div class="space-y-2">
                <label for="student_number" class="text-sm font-medium text-slate-700">Student number</label>
                <input id="student_number" type="text" name="student_number" value="{{ old('student_number') }}" required maxlength="50" autofocus autocomplete="off"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="e.g. 2026-0001">
            </div>
            <div class="space-y-2">
                <label for="lrn" class="text-sm font-medium text-slate-700">Learner Reference Number (LRN)</label>
                <input id="lrn" type="text" name="lrn" value="{{ old('lrn') }}" required inputmode="numeric" pattern="\d{12}" minlength="12" maxlength="12" autocomplete="off"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="12-digit LRN">
            </div>
            <div class="space-y-2">
                <label for="name" class="text-sm font-medium text-slate-700">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="Juan Santos Dela Cruz">
            </div>
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-slate-700">Official Gmail address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="student@gmail.com">
            </div>
            <div class="space-y-2">
                <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                <input id="password" type="password" name="password" required minlength="12" maxlength="64" autocomplete="new-password"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
                @include('auth.partials.password-requirements')
            </div>
            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" maxlength="64" autocomplete="new-password"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
            </div>
            <button type="submit" class="w-full rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white hover:bg-[#10175a]">
                Send verification code
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-[#000638] hover:underline">
            Back to sign in
        </a>
    </main>
</body>
</html>
