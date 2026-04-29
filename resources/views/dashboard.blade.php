@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', $role === 'student' ? 'Student Portal' : 'System Overview')
@section('page_subtitle', $role === 'student' ? 'Manage your document requests and grades' : 'Comprehensive system control and monitoring')

@section('content')
<div class="space-y-10">
    @if(in_array($role, ['admin', 'registrar', 'records_officer']))
    <!-- Admin/Staff Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Total Users (Admin Only) -->
        @if($role === 'admin')
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-6 group hover:shadow-xl hover:shadow-indigo-500/5 transition-all duration-300">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600 group-hover:bg-indigo-600 group-hover:text-white group-hover:rotate-6 transition-all duration-300 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400">Total Users</p>
                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $data['total_users'] }}</h3>
            </div>
        </div>
        @endif

        <!-- Total Requests -->
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-6 group hover:shadow-xl hover:shadow-indigo-500/5 transition-all duration-300">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600 group-hover:bg-indigo-600 group-hover:text-white group-hover:rotate-6 transition-all duration-300 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400">Total Requests</p>
                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $data['total_requests'] }}</h3>
            </div>
        </div>

        <!-- Pending Requests -->
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-6 group hover:shadow-xl hover:shadow-amber-500/5 transition-all duration-300">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600 group-hover:bg-amber-500 group-hover:text-white group-hover:rotate-6 transition-all duration-300 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400">Pending</p>
                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $data['pending_requests'] }}</h3>
            </div>
        </div>

        <!-- Grades Recorded -->
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-6 group hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600 group-hover:bg-emerald-600 group-hover:text-white group-hover:rotate-6 transition-all duration-300 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400">Total Grades</p>
                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $data['total_grades'] }}</h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Main Area -->
        <div class="lg:col-span-2 space-y-10">
            <!-- Welcome Card -->
            <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Welcome back, {{ auth()->user()->first_name_only ?? 'Administrator' }}!</h3>
                    <p class="text-slate-500 mt-2 max-w-md font-medium italic">
                        @if($data['pending_requests'] > 0)
                            There are currently {{ $data['pending_requests'] }} pending document requests that need your attention.
                        @else
                            Everything is looking good. No pending requests at the moment.
                        @endif
                    </p>
                    <div class="mt-8 flex gap-4">
                        <a href="{{ route('requests.index') }}" class="px-6 py-3 bg-slate-900 text-white text-xs font-black rounded-xl hover:bg-slate-800 transition-all uppercase tracking-widest shadow-lg shadow-slate-200">
                            View Requests
                        </a>
                        @if($role === 'admin')
                        <a href="{{ route('users.index') }}" class="px-6 py-3 bg-white text-slate-900 text-xs font-black rounded-xl border border-slate-200 hover:bg-slate-50 transition-all uppercase tracking-widest">
                            Manage Users
                        </a>
                        @endif
                    </div>
                </div>
                <!-- Abstract Background Shape -->
                <div class="absolute top-[-20%] right-[-10%] w-64 h-64 bg-indigo-50 rounded-full blur-3xl opacity-50"></div>
                <div class="absolute bottom-[-20%] right-[10%] w-48 h-48 bg-emerald-50 rounded-full blur-3xl opacity-50"></div>
            </div>

            <!-- Recent Requests Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-[10px] tracking-[0.3em]">Recent Requests</h3>
                    <a href="{{ route('requests.index') }}" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Student</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Document</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($data['recent_requests'] as $request)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-4">
                                    <p class="text-sm font-bold text-slate-800">{{ $request->student->name ?? 'Unknown Student' }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $request->student_number }}</p>
                                </td>
                                <td class="px-8 py-4">
                                    <span class="text-xs font-bold text-slate-600">{{ $request->document_type }}</span>
                                </td>
                                <td class="px-8 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest 
                                        {{ $request->status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                                        {{ $request->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-8 py-10 text-center">
                                    <p class="text-xs font-bold text-slate-400 italic">No recent requests found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-10">
            <!-- Quick Stats -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                <h3 class="font-black text-slate-800 uppercase text-[10px] tracking-[0.3em] mb-8">System Activity</h3>
                <div class="space-y-6">
                    @forelse($data['recent_logs'] as $log)
                    <div class="flex gap-4">
                        <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-800 leading-relaxed">{{ $log->action }}</p>
                            <p class="text-[10px] text-slate-400 font-medium uppercase tracking-widest mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest py-4">No recent activity</p>
                    @endforelse
                </div>
            </div>

            <!-- System Info -->
            <div class="bg-slate-900 rounded-3xl p-8 text-white shadow-2xl shadow-slate-200">
                <h3 class="font-black uppercase text-[10px] tracking-[0.3em] text-slate-400 mb-8">System Health</h3>
                <div class="space-y-8">
                    <div>
                        <div class="flex justify-between text-[10px] mb-3">
                            <span class="text-slate-400 font-black uppercase tracking-[0.2em]">Storage Usage</span>
                            <span class="text-slate-300 font-black">24%</span>
                        </div>
                        <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-indigo-500 h-full w-[24%] rounded-full"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300">Database Connected</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-300">Fiat Link Active</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Student Dashboard View -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2 space-y-10">
            <!-- Student Welcome -->
            <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100 relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Mabuhay, {{ auth()->user()->first_name_only }}!</h3>
                    <p class="text-slate-500 mt-2 max-w-md font-medium italic">
                        Welcome to your student portal. You can request documents and check their status here.
                    </p>
                    <div class="mt-8">
                        <a href="{{ route('student.request') }}" class="inline-flex items-center gap-3 px-8 py-4 bg-indigo-600 text-white text-xs font-black rounded-2xl hover:bg-indigo-700 transition-all uppercase tracking-widest shadow-lg shadow-indigo-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                            Request New Document
                        </a>
                    </div>
                </div>
                <div class="absolute top-[-20%] right-[-10%] w-64 h-64 bg-indigo-50 rounded-full blur-3xl opacity-50"></div>
            </div>

            <!-- Active Requests -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                        <h3 class="font-black text-slate-800 uppercase text-[10px] tracking-[0.3em]">Active Requests</h3>
                    </div>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-widest">{{ count($data['active_requests']) }} Active</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Document</th>
                                <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($data['active_requests'] as $request)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-4 text-xs font-bold text-slate-500">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-8 py-4">
                                    <span class="text-xs font-bold text-slate-800">{{ $request->document_type }}</span>
                                </td>
                                <td class="px-8 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest 
                                        {{ $request->status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600' }}">
                                        {{ $request->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-8 py-10 text-center">
                                    <p class="text-xs font-bold text-slate-400 italic">No active requests. Submit a new one above!</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- History of Requests -->
            <div class="bg-slate-50/50 rounded-3xl border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-black text-slate-400 uppercase text-[10px] tracking-[0.3em]">History of Requests</h3>
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data['request_history'] as $request)
                            <tr class="opacity-70 hover:opacity-100 transition-opacity">
                                <td class="px-8 py-4 text-[10px] font-bold text-slate-400">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-8 py-4">
                                    <span class="text-xs font-bold text-slate-500">{{ $request->document_type }}</span>
                                </td>
                                <td class="px-8 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest 
                                        {{ $request->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                                        {{ $request->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-8 py-6 text-center">
                                    <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">No history yet</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Student Sidebar -->
        <div class="space-y-10">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                <h3 class="font-black text-slate-800 uppercase text-[10px] tracking-[0.3em] mb-8">Quick Stats</h3>
                <div class="space-y-8">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending</span>
                        <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-xs font-black">{{ $data['pending_my_requests'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Completed</span>
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-black">{{ $data['request_history']->where('status', 'completed')->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total</span>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-black">{{ $data['total_requests'] }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-600 rounded-3xl p-8 text-white shadow-xl shadow-indigo-100">
                <h3 class="font-black uppercase text-[10px] tracking-[0.3em] text-indigo-200 mb-6">Need Assistance?</h3>
                <p class="text-xs font-medium leading-relaxed text-indigo-50">
                    If you have questions about your document request, please visit the registrar's office or send an email to registrar@fiatlux.edu.ph
                </p>
                <div class="mt-8">
                    <a href="mailto:registrar@fiatlux.edu.ph" class="block w-full text-center py-3 bg-white text-indigo-600 text-[10px] font-black rounded-xl uppercase tracking-widest hover:bg-indigo-50 transition-colors">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
