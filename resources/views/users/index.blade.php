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

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">All Accounts</p>
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
