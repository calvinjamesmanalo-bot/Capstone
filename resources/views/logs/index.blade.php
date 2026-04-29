@extends('layouts.app')

@section('title', 'System Logs')
@section('page_title', 'System Audit Logs')
@section('page_subtitle', 'Monitor system activities and user actions')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
            <div>
                <h3 class="font-black text-slate-800 text-lg">Activity History</h3>
                <p class="text-xs font-medium text-slate-400 mt-1">Real-time system event monitoring</p>
            </div>
            <div class="flex gap-3">
                <button class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
                <button class="px-4 py-2 text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-all">
                    Clear Logs
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                        <th class="px-8 py-6">Timestamp</th>
                        <th class="px-8 py-6">User</th>
                        <th class="px-8 py-6">Action</th>
                        <th class="px-8 py-6">Module</th>
                        <th class="px-8 py-6 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/50 transition-all group">
                        <td class="px-8 py-6 text-xs font-bold text-slate-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-indigo-50 rounded-md flex items-center justify-center text-[10px] font-black text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all uppercase">
                                    {{ $log->user ? substr($log->user->name, 0, 1) : 'S' }}
                                </div>
                                <span class="text-sm font-bold text-slate-800">{{ $log->user->name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="text-sm font-medium text-slate-600">{{ $log->action }}</div>
                            @if($log->description)
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $log->description }}</div>
                            @endif
                        </td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1 bg-slate-100 text-[10px] font-black text-slate-500 uppercase tracking-widest rounded-lg">
                                {{ $log->module }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            @if($log->status == 'success')
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-lg">
                                    {{ $log->status }}
                                </span>
                            @else
                                <span class="px-3 py-1 bg-red-50 text-red-600 text-[10px] font-black uppercase tracking-widest rounded-lg">
                                    {{ $log->status }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-12 text-center text-slate-400 font-bold">No system logs found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
