<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - {{ \App\Support\SystemContent::schoolName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.responsive-foundation')
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
<main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-7 shadow-lg">
    <img src="{{ \App\Support\SystemContent::logoUrl() }}" alt="School seal" class="h-16 w-16 rounded-full border-2 border-[#ffd22d]">
    <h1 class="mt-5 text-2xl font-black text-[#000638]">Create your private password</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">You signed in using the temporary password issued by the school. Replace it now before accessing your records.</p>
    @if($errors->any())<div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('temporary-password.update') }}" class="mt-6 space-y-5">@csrf
        <div><label for="password" class="text-sm font-bold text-slate-700">New password</label><input id="password" name="password" type="password" required minlength="12" maxlength="64" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3">@include('auth.partials.password-requirements')</div>
        <div><label for="password_confirmation" class="text-sm font-bold text-slate-700">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" maxlength="64" autocomplete="new-password" class="mt-2 w-full rounded-xl border-slate-300 px-4 py-3"></div>
        <button class="w-full rounded-xl bg-[#000638] px-5 py-3 font-bold text-white">Save password and continue</button>
    </form>
</main>
</body></html>
