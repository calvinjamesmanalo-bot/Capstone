@extends('layouts.app')

@section('title', 'My Document Requests')
@section('page_title', 'My Requests')
@section('page_subtitle', 'Track and manage your document applications')

@section('content')
<div class="max-w-6xl mx-auto space-y-10">
    <!-- Active Requests -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-800 text-2xl uppercase tracking-tight">Active Requests</h3>
                <p class="text-xs font-black text-indigo-600 mt-2 uppercase tracking-widest">In-progress applications</p>
            </div>
            <a href="{{ route('student.request') }}" class="px-6 py-3 bg-indigo-600 text-white text-xs font-black rounded-xl hover:bg-indigo-700 transition-all uppercase tracking-widest shadow-lg shadow-indigo-500/20 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                New Request
            </a>
        </div>
        
        <div class="p-10">
            @if(count($activeRequests) > 0)
                <div class="space-y-4">
                    @foreach($activeRequests as $req)
                        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex flex-col md:flex-row md:items-center justify-between group hover:bg-white hover:shadow-2xl hover:shadow-indigo-500/5 transition-all gap-6">
                            <div class="flex items-center gap-5">
                                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-800 text-base uppercase tracking-tight">{{ $req->document_type }}</h4>
                                    @if(in_array(strtolower($req->document_type), ['form 137', 'f137']) && $req->school_level)
                                        <p class="mt-1 text-xs font-black uppercase tracking-wider text-blue-700">
                                            School level: {{ match($req->school_level) {
                                                'kinder', 'elementary' => 'Kinder and Elementary',
                                                'jhs' => 'Junior High School (JHS)',
                                                'shs' => 'Senior High School (SHS)',
                                                default => strtoupper($req->school_level),
                                            } }}
                                        </p>
                                    @endif
                                    @if(in_array(strtolower($req->document_type), ['form 138', 'f138']) && $req->school_year)
                                        <p class="mt-1 text-xs font-black uppercase tracking-wider text-blue-700">School year: {{ $req->school_year }}</p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="text-[10px] font-black bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-md uppercase tracking-widest">{{ $req->ticket_number ?? 'NO TICKET' }}</span>
                                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ $req->created_at->format('M d, Y • h:i A') }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span class="text-[9px] font-black bg-slate-100 text-slate-600 px-2 py-0.5 rounded uppercase tracking-widest">
                                            {{ $req->delivery_method ? ucfirst($req->delivery_method) : 'N/A' }} • {{ $req->payment_method ? ucfirst(str_replace('_', ' ', $req->payment_method)) : 'N/A' }}
                                        </span>
                                        <span class="text-[9px] font-black {{ $req->payment_confirmed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} px-2 py-0.5 rounded uppercase tracking-widest">
                                            {{ $req->payment_confirmed ? 'Payment Confirmed' : 'Awaiting Payment' }}
                                        </span>
                                        @if($req->clearance_status)
                                            <span class="text-[9px] font-black {{ $req->clearance_status === 'cleared' ? 'bg-emerald-100 text-emerald-700' : ($req->clearance_status === 'has_balance' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }} px-2 py-0.5 rounded uppercase tracking-widest">
                                                {{ str_replace('_', ' ', $req->clearance_status) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                @if($req->payment_proof_path)
                                    <a href="{{ route('requests.receipt', $req) }}" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 bg-indigo-100 text-indigo-700 text-[10px] font-black rounded-lg hover:bg-indigo-200 transition-all uppercase tracking-widest">
                                        View Transcript Receipt
                                    </a>
                                @endif
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'processed' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'ready_to_release' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    ];
                                    $statusClass = $statusClasses[$req->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                @endphp
                                <span class="px-5 py-2.5 rounded-xl border {{ $statusClass }} text-xs font-black uppercase tracking-widest shadow-sm">
                                    {{ str_replace('_', ' ', $req->status) }}
                                </span>
                                @if($req->remarks)
                                    <p class="text-[10px] text-slate-400 italic mt-1 font-bold uppercase tracking-tight">Remarks: {{ $req->remarks }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-20 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                        <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0l-8 4-8-4" /></svg>
                    </div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">No active requests found</p>
                    <a href="{{ route('student.request') }}" class="inline-block mt-6 text-indigo-600 font-black text-xs uppercase tracking-widest hover:text-indigo-700">Submit a new request &rarr;</a>
                </div>
            @endif
        </div>
    </div>

    <!-- History of Requests -->
    <div class="bg-slate-50/50 rounded-[2.5rem] border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-100 bg-white/50">
            <h3 class="font-black text-slate-500 text-base uppercase tracking-widest">History of Requests</h3>
        </div>
        <div class="p-8">
            @if(count($requestHistory) > 0)
                <div class="space-y-3">
                    @foreach($requestHistory as $req)
                        <div class="flex items-center justify-between p-5 bg-white rounded-2xl border border-slate-100 opacity-70 hover:opacity-100 transition-all hover:shadow-lg hover:shadow-slate-200/50">
                            <div class="flex items-center gap-4">
                                <div class="text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-slate-700">{{ $req->document_type }}</h4>
                                        @if(in_array(strtolower($req->document_type), ['form 137', 'f137']) && $req->school_level)
                                            <p class="mt-1 text-xs font-bold text-blue-700">
                                                School level: {{ match($req->school_level) {
                                                    'kinder', 'elementary' => 'Kinder and Elementary',
                                                    'jhs' => 'Junior High School (JHS)',
                                                    'shs' => 'Senior High School (SHS)',
                                                    default => strtoupper($req->school_level),
                                                } }}
                                            </p>
                                        @endif
                                        @if(in_array(strtolower($req->document_type), ['form 138', 'f138']) && $req->school_year)
                                            <p class="mt-1 text-xs font-bold text-blue-700">School year: {{ $req->school_year }}</p>
                                        @endif
                                        <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded uppercase tracking-widest">{{ $req->ticket_number ?? 'N/A' }}</span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500">{{ $req->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <span class="px-4 py-1.5 rounded-lg text-xs font-black uppercase tracking-widest {{ $req->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-red-50 text-red-700 border border-red-100' }}">
                                {{ $req->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10">
                    <p class="text-xs font-bold text-slate-300 uppercase tracking-widest">No past records found</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
