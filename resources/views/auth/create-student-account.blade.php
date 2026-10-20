<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student Account - {{ \App\Support\SystemContent::schoolName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if (config('services.turnstile.enabled'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    @include('partials.responsive-foundation')
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f6f7fb] p-4">
    <main class="my-6 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
        <img src="{{ \App\Support\SystemContent::logoUrl() }}" alt="Fiat Lux Academe seal" class="mb-6 h-16 w-16 rounded-full border-2 border-[#ffd22d] object-contain">
        <h1 class="text-2xl font-bold text-[#000638]">Create Student Account</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">First, verify your details against the official student masterlist. After a match is found, you can set your Gmail and password.</p>

        @if(session('status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium leading-6 text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($remainingAccounts < 1)
        <div class="mt-6 rounded-xl border border-red-300 bg-red-50 p-5 text-red-800" role="alert">
            <p class="text-base font-bold">Account creation is unavailable for today</p>
            <p class="mt-2 text-sm leading-6">The daily limit of 200 new student accounts has been reached. You cannot process an account today. Please return tomorrow after 12:00 AM (Philippine time).</p>
        </div>
        @elseif (!$identity)
        <form action="{{ route('student.registration.lookup') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            <div class="space-y-2">
                <label for="student_number" class="text-sm font-medium text-slate-700">Student number</label>
                <input id="student_number" type="text" name="student_number" value="{{ old('student_number') }}" required maxlength="50" autofocus autocomplete="off"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="e.g. 2026-0001">
            </div>
            <div class="space-y-2">
                <label for="first_name" class="text-sm font-medium text-slate-700">First name</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required maxlength="100" autocomplete="given-name"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="Juan">
            </div>
            <div class="space-y-2">
                <label for="middle_name" class="text-sm font-medium text-slate-700">Middle name <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name') }}" maxlength="100" autocomplete="additional-name"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="Santos">
                <p class="text-xs leading-5 text-slate-500">Optional only. Your First Name and Last Name are still used to match the official masterlist.</p>
            </div>
            <div class="space-y-2">
                <label for="last_name" class="text-sm font-medium text-slate-700">Last name</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required maxlength="100" autocomplete="family-name"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40"
                    placeholder="Dela Cruz">
            </div>
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-slate-700">Gmail address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40" placeholder="student@gmail.com">
            </div>
            <div class="space-y-2">
                <label for="school_id" class="text-sm font-medium text-slate-700">Photo of School ID</label>
                <input id="school_id" type="file" name="school_id" required accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
                <p class="text-xs text-slate-500">Clear front photo. JPG, PNG, or WebP; maximum 5 MB.</p>
            </div>
            <div class="space-y-2">
                <label for="school_id_selfie" class="text-sm font-medium text-slate-700">Selfie holding your School ID</label>
                <input id="school_id_selfie" type="file" name="school_id_selfie" required accept="image/jpeg,image/png,image/webp" capture="user" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
                <p class="text-xs text-slate-500">Your face and the ID must both be clearly visible. Maximum 5 MB.</p>
            </div>
            @if (config('services.turnstile.enabled'))
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-action="student_registration"></div>
            @endif
            <button type="submit" class="w-full rounded-lg bg-[#000638] py-3 text-sm font-semibold text-white hover:bg-[#10175a]">Submit for account verification</button>
            <p class="text-center text-xs leading-5 text-slate-500">Your photos are private and can only be reviewed by the Admin or Registrar. An account-creation link will be emailed after approval.</p>
        </form>
        @else
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Student record matched</p>
            <p class="mt-1 font-bold text-[#000638]">{{ $identity['name'] }}</p>
            <p class="text-sm text-slate-600">Student Number: {{ $identity['student_number'] }}</p>
        </div>
        <form action="{{ route('student.registration.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm"><span class="text-slate-500">Approved Gmail:</span> <strong>{{ $identity['email'] }}</strong></div>
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
                Create and activate account
            </button>
            <p class="text-center text-xs leading-5 text-slate-500">Your approved email link is sufficient. No verification code is required.</p>
        </form>
        <a href="{{ route('student.registration.request', ['restart' => 1]) }}" class="mt-4 block text-center text-sm font-semibold text-slate-600 hover:underline">Use different student details</a>
        @endif

        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-[#000638] hover:underline">
            Back to sign in
        </a>
    </main>
    @include('partials.image-upload-preview')
</body>
</html>
