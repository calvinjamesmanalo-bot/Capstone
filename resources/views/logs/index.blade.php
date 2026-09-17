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
                <form action="{{ route('logs.clear') }}" method="POST"
                      data-confirm="Clear all activity logs and document verification audit logs? This cannot be undone."
                      onsubmit="return confirm(this.dataset.confirm);">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-all">
                        Clear Logs
                    </button>
                </form>
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
                        <td colspan="5"><x-empty-state :heading="$logs->total() ? 'No activity on this page' : 'No activity recorded yet'" description="Activity entries appear here as people use the system." :action-url="$logs->total() ? route('logs.index') : null" action-label="View first page" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $logs->links() }}
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-slate-50/50">
            <h3 class="font-black text-slate-800 text-lg">Document Verification Audit</h3>
            <p class="text-xs font-medium text-slate-400 mt-1">Who checked an issued document, when, and the verification result</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                        <th class="px-8 py-6">Timestamp</th>
                        <th class="px-8 py-6">Verifier</th>
                        <th class="px-8 py-6">Control Number</th>
                        <th class="px-8 py-6">Document</th>
                        <th class="px-8 py-6 text-right">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($verificationAudits as $audit)
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="px-8 py-6 text-xs font-bold text-slate-500">{{ $audit->verified_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-8 py-6">
                                <div class="text-sm font-bold text-slate-800">{{ $audit->user?->name ?? $audit->ip_address ?? 'Public visitor' }}</div>
                                <div class="max-w-xs truncate text-[10px] text-slate-400" title="{{ $audit->user_agent }}">{{ $audit->user_agent }}</div>
                            </td>
                            <td class="px-8 py-6 text-xs font-black text-slate-700">{{ $audit->control_number ?? 'Unknown token' }}</td>
                            <td class="px-8 py-6 text-sm font-medium text-slate-600">{{ $audit->documentAuthenticity?->document_type ?? 'Not found' }}</td>
                            <td class="px-8 py-6 text-right">
                                <span class="px-3 py-1 {{ in_array($audit->result, ['authentic', 'file_match'], true) ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }} text-[10px] font-black uppercase tracking-widest rounded-lg">
                                    {{ str_replace('_', ' ', $audit->result) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state :heading="$verificationAudits->total() ? 'No verifications on this page' : 'No document verifications recorded'" description="Verification entries appear here when an issued document is checked." :action-url="$verificationAudits->total() ? route('logs.index') : null" action-label="View first page" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $verificationAudits->links() }}
    </div>
</div>
@endsection
