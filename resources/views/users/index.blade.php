@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Directory')
@section('page_subtitle', 'Manage staff and student accounts')

@section('content')
@php
    $totalAccounts = $staffUsers->count() + $studentUsers->count();
@endphp

<div id="account-directory-top" class="space-y-6 scroll-mt-24">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-indigo-600">User Management</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Account Directory</h2>
            <p class="mt-1 text-sm text-slate-500">Student accounts are separated from staff for easier management.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
        <a href="#student-accounts" class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100 sm:w-auto">Go to Student Accounts ↓</a>
        @if(auth()->user()->role === 'admin')
        <form method="POST" action="{{ route('users.demo-student') }}" onsubmit="return confirm('Create or refresh Calvin James Manalo with complete Kinder-to-Grade-12 demo records?')">
            @csrf
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-indigo-300 bg-indigo-50 px-5 py-3 text-sm font-bold text-indigo-800 hover:bg-indigo-100">Create Full Demo Student</button>
        </form>
        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New User
        </a>
        @endif
        </div>
    </div>

    @if(session('issued_student_credential'))
        @php($issued = session('issued_student_credential'))
        <section class="rounded-2xl border-2 border-amber-400 bg-amber-50 p-6 shadow-sm" role="status">
            <p class="text-sm font-black uppercase tracking-wide text-amber-800">Give this to the student now</p>
            <h2 class="mt-1 text-xl font-black text-[#000638]">{{ $issued['name'] }}</h2>
            <p class="mt-2 text-sm text-amber-900">This random temporary password is shown only on this page. The student must change it immediately after signing in.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-white p-4"><p class="text-xs font-bold text-slate-500">Student Number</p><p class="mt-1 select-all font-mono text-lg font-black">{{ $issued['student_number'] }}</p></div>
                <div class="rounded-xl bg-white p-4"><p class="text-xs font-bold text-slate-500">Temporary Password</p><p class="mt-1 select-all font-mono text-lg font-black tracking-wide">{{ $issued['temporary_password'] }}</p></div>
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="account-search" class="text-sm font-semibold text-slate-700">Check whether an account exists</label>
                <p class="mt-1 text-xs text-slate-500">Search securely by email address, student number, LRN, or name. Admins and Registrars can assist student accounts here.</p>
                <input id="account-search" name="search" type="search" value="{{ $search }}" maxlength="255"
                    placeholder="Email, student number, LRN, or name"
                    class="mt-3 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            </div>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Check account
            </button>
            @if($search !== '')
                <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Clear
                </a>
            @endif
        </form>

        @if($search !== '')
            @if($totalAccounts > 0)
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                    {{ $totalAccounts === 1 ? 'Account exists.' : $totalAccounts.' matching accounts exist.' }} Review the verified-email and official-roster status below.
                </div>
            @else
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800" role="status">
                    No account matches “{{ $search }}”. Check the spelling or create the account from an official record.
                </div>
            @endif
        @endif
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">{{ $search !== '' ? 'Matching Accounts' : 'All Accounts' }}</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalAccounts) }}</p>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <p class="text-sm font-medium text-indigo-700">Staff & Administration</p>
            <p class="mt-2 text-3xl font-bold text-indigo-900">{{ number_format($staffUsers->count()) }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-sm font-medium text-emerald-700">Students</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ number_format($studentUsers->count()) }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-medium text-amber-700">Archived Accounts</p>
            <p class="mt-2 text-3xl font-bold text-amber-900">{{ number_format($archivedUsers->count()) }}</p>
            <p class="mt-1 text-xs text-amber-800">Automatically deleted after 6 months</p>
        </div>
    </div>

    @include('users.partials.account-table', [
        'title' => 'Staff & Administration',
        'description' => 'Administrators, registrars, and records officers',
        'users' => $staffUsers,
        'isStudentTable' => false,
        'accent' => 'indigo',
    ])

    <div id="student-accounts" class="scroll-mt-24">
    @include('users.partials.account-table', [
        'title' => 'Student Accounts',
        'description' => 'Sorted alphabetically by student name',
        'users' => $studentUsers,
        'isStudentTable' => true,
        'accent' => 'emerald',
    ])
    </div>

    @if(auth()->user()->role === 'admin')
    <section class="overflow-hidden rounded-2xl border-2 border-amber-200 bg-white shadow-sm">
        <div class="border-b border-amber-200 bg-amber-50 px-6 py-5">
            <h3 class="text-lg font-bold text-amber-950">Archived Accounts</h3>
            <p class="mt-1 text-sm text-amber-800">Archived accounts cannot sign in. Restore within 6 months or they will be permanently deleted automatically.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase text-slate-500"><tr><th class="px-6 py-4">Account</th><th class="px-6 py-4">Previous role</th><th class="px-6 py-4">Archived</th><th class="px-6 py-4">Automatic deletion</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($archivedUsers as $user)
                        <tr><td class="px-6 py-4"><p class="font-bold text-slate-900">{{ $user->display_name }}</p><p class="text-xs text-slate-500">{{ $user->student_number ?: $user->email }}</p></td><td class="px-6 py-4 capitalize">{{ str_replace('_', ' ', $user->archivedOriginalRole()) }}</td><td class="px-6 py-4">{{ $user->updated_at->format('M d, Y h:i A') }}</td><td class="px-6 py-4"><span class="font-bold text-red-700">{{ $user->updated_at->copy()->addMonthsNoOverflow(6)->format('M d, Y') }}</span><p class="mt-1 text-xs text-slate-500">{{ $user->updated_at->copy()->addMonthsNoOverflow(6)->diffForHumans() }}</p></td><td class="px-6 py-4"><div class="flex justify-end gap-2"><form method="POST" action="{{ route('users.restore', $user->id) }}">@csrf<button class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Restore</button></form><form method="POST" action="{{ route('users.force-destroy', $user->id) }}" onsubmit="return confirm('Permanently delete this archived account now? This cannot be undone.')">@csrf @method('DELETE')<button class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-100">Delete permanently</button></form></div></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">No archived accounts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif

    <div class="sticky bottom-5 z-30 flex justify-end gap-2 pointer-events-none">
        <a href="#student-accounts" class="pointer-events-auto rounded-full border border-emerald-300 bg-white px-4 py-3 text-sm font-black text-emerald-800 shadow-lg hover:bg-emerald-50">Students ↓</a>
        <a href="#account-directory-top" class="pointer-events-auto rounded-full bg-[#000638] px-4 py-3 text-sm font-black text-white shadow-lg hover:bg-[#10175a]">Back to top ↑</a>
    </div>
</div>
@endsection
