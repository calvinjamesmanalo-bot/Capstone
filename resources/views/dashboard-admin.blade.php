@extends('layouts.app')

@section('title', 'Admin Control Center')
@section('page_title', 'Admin Control Center')
@section('page_subtitle', 'System oversight, access control, and operational health')

@section('content')
@php
    $statuses = $data['request_statuses'];
    $roles = $data['user_roles'];
    $pending = (int) ($statuses['pending'] ?? 0);
    $processing = (int) ($statuses['processing'] ?? 0);
    $forReview = (int) ($statuses['processed'] ?? 0);
    $ready = (int) ($statuses['ready_to_release'] ?? 0);
    $active = $pending + $processing + $forReview + $ready;
@endphp

<div class="request-a11y min-w-0 space-y-6">
    <section class="overflow-hidden rounded-2xl bg-[#000638] text-white shadow-sm">
        <div class="grid gap-6 px-6 py-7 lg:grid-cols-[1fr_auto] lg:items-center lg:px-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-[#ffd22d]">Administrator workspace</p>
                <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Good day, {{ auth()->user()->first_name_only ?? 'Administrator' }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Monitor the request pipeline, manage account access, and review system activity from one place.</p>
            </div>
            <div class="flex flex-wrap gap-2 lg:justify-end">
                <a href="{{ route('requests.index') }}" class="rounded-lg bg-[#ffd22d] px-4 py-2.5 text-sm font-bold text-[#000638] hover:bg-[#ffe36f]">Open requests</a>
                <a href="{{ route('users.create') }}" class="rounded-lg border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/15">Create account</a>
            </div>
        </div>
    </section>

    <section aria-labelledby="attention-title">
        <div class="mb-3 flex items-end justify-between gap-4">
            <div>
                <h2 id="attention-title" class="text-lg font-bold text-slate-900">Needs attention</h2>
                <p class="mt-1 text-sm text-slate-500">Items that may require an administrator decision.</p>
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('requests.index', ['status' => 'pending']) }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4 transition hover:border-amber-300">
                <p class="text-xs font-bold uppercase tracking-wide text-amber-800">New requests</p>
                <p class="mt-2 text-3xl font-bold text-amber-950">{{ $pending }}</p>
                <p class="mt-1 text-xs text-amber-800">Waiting to be assigned for processing</p>
            </a>
            <a href="{{ route('requests.index', ['status' => 'processed']) }}" class="rounded-xl border border-blue-200 bg-blue-50 p-4 transition hover:border-blue-300">
                <p class="text-xs font-bold uppercase tracking-wide text-blue-800">Registrar review</p>
                <p class="mt-2 text-3xl font-bold text-blue-950">{{ $forReview }}</p>
                <p class="mt-1 text-xs text-blue-800">Forwarded documents awaiting review</p>
            </a>
            <a href="{{ route('requests.index', ['status' => 'ready_to_release']) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 transition hover:border-emerald-300">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Ready for release</p>
                <p class="mt-2 text-3xl font-bold text-emerald-950">{{ $ready }}</p>
                <p class="mt-1 text-xs text-emerald-800">Reviewed documents awaiting release</p>
            </a>
            <a href="{{ route('users.index') }}" class="rounded-xl border border-rose-200 bg-rose-50 p-4 transition hover:border-rose-300">
                <p class="text-xs font-bold uppercase tracking-wide text-rose-800">Unverified accounts</p>
                <p class="mt-2 text-3xl font-bold text-rose-950">{{ $data['unverified_users'] }}</p>
                <p class="mt-1 text-xs text-rose-800">Accounts without verified email access</p>
            </a>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="font-bold text-slate-900">Request operations</h2>
                        <p class="mt-1 text-sm text-slate-500">Current workload across the complete process.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $active }} active</span>
                </div>
                <div class="grid divide-y divide-slate-200 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                    @foreach([
                        ['processing', 'Processing', $processing],
                        ['processed', 'For review', $forReview],
                        ['ready_to_release', 'Ready', $ready],
                        ['completed', 'Completed today', $data['completed_today']],
                    ] as [$status, $label, $count])
                        <a href="{{ $status === 'completed' ? route('requests.history') : route('requests.index', ['status' => $status]) }}" class="p-5 hover:bg-slate-50">
                            <p class="text-sm text-slate-500">{{ $label }}</p>
                            <p class="mt-2 text-2xl font-bold text-[#000638]">{{ $count }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="font-bold text-slate-900">Latest requests</h2>
                        <p class="mt-1 text-sm text-slate-500">A quick view of recent submissions and changes.</p>
                    </div>
                    <a href="{{ route('requests.index') }}" class="text-sm font-bold text-indigo-700 hover:underline">View all</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($data['recent_requests'] as $documentRequest)
                        <a href="{{ route('requests.index', ['search' => $documentRequest->ticket_number ?: $documentRequest->student_number]) }}" class="grid gap-2 px-5 py-4 hover:bg-slate-50 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $documentRequest->student->name ?? 'Unknown student' }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $documentRequest->ticket_number ?? '#'.$documentRequest->id }} &middot; {{ $documentRequest->document_type }}</p>
                            </div>
                            <x-request-status-badge :status="$documentRequest->status" />
                        </a>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-slate-500">No requests have been submitted.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-900">System administration</h2>
                <p class="mt-1 text-sm text-slate-500">Configuration and oversight tools.</p>
                <div class="mt-4 space-y-2">
                    @foreach([
                        ['users.index', 'User accounts', 'Roles, access, and student accounts'],
                        ['grade-portal.index', 'Academic records', 'Grade sheets and uploaded records'],
                        ['analytics.index', 'Reports & analytics', 'Request volume and trends'],
                        ['logs.index', 'Audit & security', 'System actions and verification events'],
                    ] as [$routeName, $label, $description])
                        <a href="{{ route($routeName) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 hover:border-indigo-300 hover:bg-indigo-50/40">
                            <span>
                                <span class="block text-sm font-bold text-slate-900">{{ $label }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $description }}</span>
                            </span>
                            <span class="text-slate-400" aria-hidden="true">&rarr;</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-slate-900">Account overview</h2>
                    <span class="text-xs font-bold text-slate-500">{{ $data['total_users'] }} total</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    @foreach(['admin' => 'Admins', 'registrar' => 'Registrars', 'records_officer' => 'Records staff', 'student' => 'Students'] as $roleKey => $roleLabel)
                        <div class="rounded-xl bg-slate-50 p-3">
                            <dt class="text-xs text-slate-500">{{ $roleLabel }}</dt>
                            <dd class="mt-1 text-xl font-bold text-slate-900">{{ (int) ($roles[$roleKey] ?? 0) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-slate-900">Recent system activity</h2>
                    <a href="{{ route('logs.index') }}" class="text-xs font-bold text-indigo-700 hover:underline">Open audit log</a>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse($data['recent_logs']->take(5) as $log)
                        <div class="border-l-2 border-slate-200 pl-3">
                            <p class="text-sm font-semibold text-slate-800">{{ $log->action }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->user?->display_name ?? 'System' }} &middot; {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
