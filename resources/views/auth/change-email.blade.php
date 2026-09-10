@extends('layouts.app')

@section('title', 'Account Security')
@section('page_title', 'Account Security')
@section('page_subtitle', 'Manage your verified email address')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-8">
            <h2 class="text-xl font-black text-slate-800">Change email address</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">You must confirm your password and verify the new email before it replaces your official address.</p>
        </div>

        <div class="p-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="mb-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                Current verified email: <strong>{{ $user->email }}</strong>
                @if ($user->pending_email)
                    <div class="mt-2 text-amber-700">Awaiting verification: <strong>{{ $user->pending_email }}</strong></div>
                @endif
            </div>

            <form action="{{ route('student.email.request') }}" method="POST" class="space-y-5">
                @csrf
                <div class="space-y-2">
                    <label for="current_password" class="text-sm font-semibold text-slate-700">Current password</label>
                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
                    @error('current_password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="email" class="text-sm font-semibold text-slate-700">New email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->pending_email) }}" required autocomplete="email"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
                    @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="rounded-xl bg-[#000638] px-6 py-3 text-sm font-bold text-white hover:bg-[#10175a]">Send verification link</button>
            </form>
        </div>
    </div>
</div>
@endsection
