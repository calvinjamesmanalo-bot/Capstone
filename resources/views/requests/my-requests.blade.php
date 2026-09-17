@extends('layouts.app')

@section('title', 'My Document Requests')
@section('page_title', 'My Requests')
@section('page_subtitle', 'Track and manage your document applications')

@section('content')
<div class="request-a11y mx-auto min-w-0 max-w-6xl space-y-10">
    <!-- Active Requests -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-slate-50 bg-slate-50/50 p-10 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-black text-slate-800 text-2xl uppercase tracking-tight">Active Requests</h3>
                <p class="text-xs font-black text-indigo-600 mt-2 uppercase tracking-widest">In-progress applications</p>
            </div>
            <a href="{{ route('student.request') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-xs font-black text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-700 sm:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                New Request
            </a>
        </div>
        
        <div class="min-w-0 p-5 sm:p-10">
            @if(count($activeRequests) > 0)
                <div class="space-y-4">
                    @foreach($activeRequests as $req)
                        <div class="flex min-w-0 flex-col justify-between gap-4 rounded-3xl border border-slate-100 bg-slate-50 p-4 transition-all hover:bg-white hover:shadow-2xl hover:shadow-indigo-500/5 sm:p-6 md:flex-row md:items-center md:gap-6 group">
                            <div class="flex min-w-0 items-start gap-4 sm:items-center sm:gap-5">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 bg-white rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
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
                                            {{ $req->payment_confirmed ? 'Document Payment Confirmed' : 'Document Payment Pending' }}
                                        </span>
                                        @if($req->clearance_status)
                                            <span class="text-[9px] font-black {{ $req->clearance_status === 'cleared' ? 'bg-emerald-100 text-emerald-700' : ($req->clearance_status === 'has_balance' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }} px-2 py-0.5 rounded uppercase tracking-widest">
                                                {{ str_replace('_', ' ', $req->clearance_status) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex min-w-0 flex-col items-start gap-2 md:items-end">
                                <a href="{{ route('requests.receipt', $req) }}" target="_blank" rel="noopener noreferrer" class="w-full rounded-lg bg-indigo-100 px-3 py-2 text-center text-xs font-black uppercase tracking-widest text-indigo-700 transition-all hover:bg-indigo-200 sm:w-auto">Print Request Receipt</a>
                                @if($req->payment_proof_path)
                                    <a href="{{ route('requests.receipt.uploaded', $req) }}" target="_blank" rel="noopener noreferrer" class="w-full rounded-lg bg-slate-100 px-3 py-2 text-center text-xs font-black uppercase tracking-widest text-slate-700 transition-all hover:bg-slate-200 sm:w-auto">View Transcript Receipt (Uploaded Accounting File)</a>
                                @endif
                                <x-request-status-badge :status="$req->status" />
                                @include('requests.partials.status-timeline', ['histories' => $req->publicStatusHistories, 'staff' => false])
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state heading="No active requests" description="You have no document requests in progress. Submit a request when you need a school document." :action-url="auth()->user()->role === 'student' ? route('student.request') : null" action-label="Submit a new request" />
            @endif
        </div>
    </div>

    <!-- History of Requests -->
    <div class="bg-slate-50/50 rounded-[2.5rem] border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-100 bg-white/50">
            <h3 class="font-black text-slate-500 text-base uppercase tracking-widest">History of Requests</h3>
        </div>
        <div class="min-w-0 p-5 sm:p-8">
            @if(count($requestHistory) > 0)
                <div class="space-y-3">
                    @foreach($requestHistory as $req)
                        <div class="flex flex-col gap-4 rounded-2xl border border-slate-100 bg-white p-5 opacity-70 transition-all hover:opacity-100 hover:shadow-lg hover:shadow-slate-200/50 sm:flex-row sm:items-center sm:justify-between">
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
                                    @include('requests.partials.status-timeline', ['histories' => $req->publicStatusHistories, 'staff' => false])
                                </div>
                            </div>
                            <x-request-status-badge :status="$req->status" />
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state heading="No request history yet" description="Your released and rejected document requests will appear here." />
            @endif
        </div>
    </div>
</div>
@endsection
