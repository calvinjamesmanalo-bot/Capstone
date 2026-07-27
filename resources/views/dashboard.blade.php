@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', $role === 'student' ? 'Student Portal' : 'System Overview')
@section('page_subtitle', $role === 'student' ? 'Manage your document requests and grades' : 'Comprehensive system control and monitoring')

@section('content')
<div class="space-y-8">
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
                    <a href="{{ route('requests.index') }}" class="px-4 py-2 bg-[#000638] text-white text-sm font-medium rounded-lg hover:bg-[#10175a] transition-colors">
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
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-3">Student</th>
                                <th class="px-6 py-3">Document</th>
                                <th class="px-6 py-3 text-center">Status</th>
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
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $request->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ ucwords(str_replace('_', ' ', $request->status)) }}
                                    </span>
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
                    <a href="{{ route('student.request') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#000638] text-white text-sm font-medium rounded-lg hover:bg-[#10175a] transition-colors">
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
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Document</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data['active_requests'] as $request)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $request->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $request->document_type }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $request->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ ucwords(str_replace('_', ' ', $request->status)) }}
                                    </span>
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
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data['request_history'] as $request)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $request->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $request->document_type }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $request->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ ucwords(str_replace('_', ' ', $request->status)) }}
                                    </span>
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
