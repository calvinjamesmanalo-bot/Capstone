@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', $role === 'student' ? 'Student Portal' : 'System Overview')
@section('page_subtitle', $role === 'student' ? 'Manage your document requests and grades' : 'Comprehensive system control and monitoring')

@section('content')
<div class="request-a11y min-w-0 space-y-8">
    @php
        $quickActions = match ($role) {
            'admin' => [
                ['route' => 'requests.index', 'title' => 'Manage Requests', 'description' => 'Review and process document requests.', 'count' => $data['pending_requests'], 'count_label' => 'pending requests'],
                ['route' => 'users.index', 'title' => 'Manage Users', 'description' => 'Manage staff and student accounts.', 'count' => $data['total_users'], 'count_label' => 'users'],
                ['route' => 'school-forms.records', 'title' => 'Grade Sheet Records', 'description' => 'Upload and review class grade sheets.', 'count' => null],
                ['route' => 'grade-portal.index', 'title' => 'Grade Portal', 'description' => 'Find and manage student grade uploads.', 'count' => null],
                ['route' => 'analytics.index', 'title' => 'View Analytics', 'description' => 'Review request and system trends.', 'count' => null],
                ['route' => 'logs.index', 'title' => 'Activity Logs', 'description' => 'Review system and verification activity.', 'count' => null],
                ['route' => 'settings.index', 'title' => 'System Settings', 'description' => 'Configure school and portal settings.', 'count' => null],
            ],
            'registrar' => [
                ['route' => 'requests.index', 'title' => 'Review Requests', 'description' => 'Review processed requests awaiting action.', 'count' => null],
                ['route' => 'requests.history', 'title' => 'Request History', 'description' => 'View released and rejected requests.', 'count' => null],
                ['route' => 'school-forms.records', 'title' => 'Grade Sheet Records', 'description' => 'Review class attendance and grade sheets.', 'count' => null],
                ['route' => 'grade-portal.index', 'title' => 'Grade Portal', 'description' => 'Find and manage student grade uploads.', 'count' => null],
                ['route' => 'certifications.index', 'title' => 'Certifications', 'description' => 'Prepare certification documents.', 'count' => null],
            ],
            'records_officer' => [
                ['route' => 'requests.index', 'title' => 'Manage Requests', 'description' => 'Process active student document requests.', 'count' => $data['pending_requests'], 'count_label' => 'pending requests'],
                ['route' => 'requests.history', 'title' => 'Request History', 'description' => 'View released and rejected requests.', 'count' => null],
                ['route' => 'school-forms.home', 'title' => 'School Forms', 'description' => 'Prepare Form 137 and Form 138 records.', 'count' => null],
                ['route' => 'school-forms.records', 'title' => 'Grade Sheet Records', 'description' => 'Upload and review class grade sheets.', 'count' => null],
                ['route' => 'certifications.index', 'title' => 'Certifications', 'description' => 'Prepare certification documents.', 'count' => null],
            ],
            'student' => [
                ['route' => 'student.request', 'title' => 'Request a Document', 'description' => 'Start a new school document request.', 'count' => null],
                ['route' => 'student.my-requests', 'title' => 'My Requests', 'description' => 'Track active requests and view history.', 'count' => $data['total_requests'], 'count_label' => 'my requests'],
            ],
            default => [],
        };
    @endphp

    @if($quickActions)
        <section aria-labelledby="quick-actions-heading">
            <div class="mb-4">
                <h2 id="quick-actions-heading" class="text-lg font-semibold text-slate-900">Quick actions</h2>
                <p class="mt-1 text-sm text-slate-600">Open the tools available for your role.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($quickActions as $action)
                    <a href="{{ route($action['route']) }}"
                       data-quick-action="{{ $action['route'] }}"
                       class="group flex min-h-36 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/40">
                        <div class="flex items-start justify-between gap-4">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#000638] text-[#ffd22d] transition group-hover:bg-[#10175a]" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-6 4h4m-6 8h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            </span>
                            @if($action['count'] !== null)
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700" aria-label="{{ $action['count'] }} {{ $action['count_label'] }}">{{ $action['count'] }}</span>
                            @endif
                        </div>
                        <div class="mt-5">
                            <h3 class="font-semibold text-slate-900 group-hover:text-indigo-800">{{ $action['title'] }}</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-600">{{ $action['description'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if(in_array($role, ['admin', 'registrar', 'records_officer']))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @if($role === 'admin')
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-sm text-slate-500">Total users</p>
            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $data['total_users'] }}</h3>
        </div>
        @endif

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-sm text-slate-500">Total requests</p>
            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $data['total_requests'] }}</h3>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-sm text-slate-500">Pending requests</p>
            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $data['pending_requests'] }}</h3>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-sm text-slate-500">Total grades</p>
            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $data['total_grades'] }}</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Welcome back, {{ auth()->user()->first_name_only ?? 'Administrator' }}!</h3>
                <p class="mt-2 text-sm text-slate-600 max-w-2xl">
                    @if($data['pending_requests'] > 0)
                        There are currently {{ $data['pending_requests'] }} pending document requests that need your attention.
                    @else
                        Everything is looking good. No pending requests at the moment.
                    @endif
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('requests.index') }}" class="rounded-lg bg-[#000638] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#10175a]">
                        View requests
                    </a>
                    @if($role === 'admin')
                    <a href="{{ route('users.index') }}" class="px-4 py-2 bg-white text-slate-800 text-sm font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors">
                        Manage users
                    </a>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Recent requests</h3>
                    <a href="{{ route('requests.index') }}" class="text-sm font-semibold text-[#000638] hover:underline">View all</a>
                </div>
                <p id="recent-requests-scroll-hint" class="px-6 pt-3 text-xs text-slate-600 sm:hidden">Scroll sideways to view all request details.</p>
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Recent requests table" aria-describedby="recent-requests-scroll-hint">
                    <table class="w-full min-w-[480px] text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th scope="col" class="px-6 py-3">Student</th>
                                <th scope="col" class="px-6 py-3">Document</th>
                                <th scope="col" class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data['recent_requests'] as $request)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $request->student->name ?? 'Unknown Student' }}</p>
                                    <p class="text-xs text-slate-500">{{ $request->student_number }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $request->document_type }}</td>
                                <td class="px-6 py-4 text-center">
                                    <x-request-status-badge :status="$request->status" />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-slate-500">No recent requests found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-slate-900">System activity</h3>
                <div class="mt-4 space-y-4">
                    @forelse($data['recent_logs'] as $log)
                    <div class="flex gap-3">
                        <div class="w-9 h-9 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $log->action }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-slate-500">No recent activity.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Welcome, {{ auth()->user()->first_name_only }}!</h3>
                <p class="mt-2 text-sm text-slate-600 max-w-2xl">
                    Welcome to your student portal. You can request documents and check their status here.
                </p>
                <div class="mt-5">
                    <a href="{{ route('student.request') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#000638] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#10175a] sm:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        Request new document
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Active requests</h3>
                    <span class="text-sm text-slate-600">{{ count($data['active_requests']) }} active</span>
                </div>
                <p id="active-requests-scroll-hint" class="px-6 pt-3 text-xs text-slate-600 sm:hidden">Scroll sideways to view all request details.</p>
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Active requests table" aria-describedby="active-requests-scroll-hint">
                    <table class="w-full min-w-[480px] text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th scope="col" class="px-6 py-3">Date</th>
                                <th scope="col" class="px-6 py-3">Document</th>
                                <th scope="col" class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data['active_requests'] as $request)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $request->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $request->document_type }}</td>
                                <td class="px-6 py-4 text-center">
                                    <x-request-status-badge :status="$request->status" />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-slate-500">No active requests. Submit a new one above.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">Request history</h3>
                </div>
                <p id="request-history-scroll-hint" class="px-6 pt-3 text-xs text-slate-600 sm:hidden">Scroll sideways to view all request details.</p>
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Request history table" aria-describedby="request-history-scroll-hint">
                    <table class="w-full min-w-[480px] text-left">
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data['request_history'] as $request)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $request->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $request->document_type }}</td>
                                <td class="px-6 py-4 text-center">
                                    <x-request-status-badge :status="$request->status" />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-slate-500">No history yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-slate-900">Quick stats</h3>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Pending</span>
                        <span class="text-sm font-medium text-slate-900">{{ $data['pending_my_requests'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Completed</span>
                        <span class="text-sm font-medium text-slate-900">{{ $data['request_history']->where('status', 'completed')->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600">Total</span>
                        <span class="text-sm font-medium text-slate-900">{{ $data['total_requests'] }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-slate-900">Need assistance?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    If you have questions about your document request, please visit the registrar's office or send an email to registrar@fiatlux.edu.ph.
                </p>
                <div class="mt-4">
                    <a href="mailto:registrar@fiatlux.edu.ph" class="inline-flex px-4 py-2 bg-[#000638] text-white text-sm font-medium rounded-lg hover:bg-[#10175a] transition-colors">
                        Contact support
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
