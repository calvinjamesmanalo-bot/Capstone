@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Directory')
@section('page_subtitle', 'Manage staff and student accounts')

@section('content')
@php
    $totalAccounts = $staffUsers->count() + $studentUsers->count();
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-indigo-600">User Management</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Account Directory</h2>
            <p class="mt-1 text-sm text-slate-500">Student accounts are separated from staff for easier management.</p>
        </div>
        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New User
        </a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="account-search" class="text-sm font-semibold text-slate-700">Check whether an account exists</label>
                <p class="mt-1 text-xs text-slate-500">Search securely by email address, student number, LRN, or name. This tool is available only to administrators.</p>
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

    <div class="grid gap-4 sm:grid-cols-3">
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
    </div>

    @include('users.partials.account-table', [
        'title' => 'Staff & Administration',
        'description' => 'Administrators, registrars, and records officers',
        'users' => $staffUsers,
        'isStudentTable' => false,
        'accent' => 'indigo',
    ])

    @include('users.partials.account-table', [
        'title' => 'Student Accounts',
        'description' => 'Sorted alphabetically by student name',
        'users' => $studentUsers,
        'isStudentTable' => true,
        'accent' => 'emerald',
    ])
</div>
@endsection
