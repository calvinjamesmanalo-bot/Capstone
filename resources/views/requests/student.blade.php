@extends('layouts.app')

@section('title', 'Request Document')
@section('page_title', 'Student Request Portal')
@section('page_subtitle', 'Submit a new document request to the registrar')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Request Form -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden h-fit">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50">
            <h3 class="font-black text-slate-800 text-xl text-center uppercase tracking-tight">New Request</h3>
            <p class="text-[10px] font-black text-indigo-600 mt-2 text-center uppercase tracking-widest">Submit a new document request</p>
        </div>
        
        <form action="{{ route('student.request.store') }}" method="POST" class="p-10 space-y-8">
            @csrf
            
            @if(auth()->check() && auth()->user()->role === 'student')
                <div class="p-8 bg-indigo-50 rounded-3xl border border-indigo-100 space-y-4">
                    <div class="flex items-center justify-between border-b border-indigo-100/50 pb-4">
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Student ID</span>
                        <span class="text-sm font-black text-indigo-700 uppercase tracking-tight">{{ auth()->user()->student_number }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Full Name</span>
                        <span class="text-sm font-black text-indigo-700 uppercase tracking-tight text-right">{{ auth()->user()->display_name }}</span>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Student Number</label>
                    <input type="text" name="student_number" required value="{{ $studentNumber ?? '' }}" placeholder="e.g. 2023-0001"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300">
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Full Name</label>
                    <input type="text" name="name" required value="{{ session('student_name') ?? '' }}" placeholder="e.g. Juan Dela Cruz"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300">
                </div>
            @endif

            <div class="space-y-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Document Type</label>
                <select name="document_type" required
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 appearance-none">
                    <option value="Form 137">Form 137 (Permanent Record)</option>
                    <option value="Good Moral">Certificate of Good Moral</option>
                    <option value="Diploma">Diploma</option>
                    <option value="Certification">General Certification</option>
                </select>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-5 bg-indigo-600 text-white text-xs font-black rounded-2xl shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all uppercase tracking-[0.2em] flex items-center justify-center gap-3 active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Submit Request
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Requests -->
    <div class="space-y-8">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-10 border-b border-slate-50 bg-slate-50/50">
                <h3 class="font-black text-slate-800 text-xl uppercase tracking-tight">Active Requests</h3>
                <p class="text-[10px] font-black text-indigo-600 mt-2 uppercase tracking-widest">In-progress applications</p>
            </div>
            
            <div class="p-10">
                @if(count($activeRequests) > 0)
                    <div class="space-y-4">
                        @foreach($activeRequests as $req)
                            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex items-center justify-between group hover:bg-white hover:shadow-2xl hover:shadow-indigo-500/5 transition-all">
                                <div class="flex items-center gap-5">
                                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-800 text-sm uppercase tracking-tight">{{ $req->document_type }}</h4>
                                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $req->created_at->format('M d, Y • h:i A') }}</p>
                                    </div>
                                </div>
                                <div>
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                            'processing' => 'bg-blue-50 text-blue-600 border-blue-100',
                                        ];
                                        $statusClass = $statusClasses[$req->status] ?? 'bg-slate-50 text-slate-600 border-slate-100';
                                    @endphp
                                    <span class="px-4 py-2 rounded-xl border {{ $statusClass }} text-[10px] font-black uppercase tracking-widest shadow-sm">
                                        {{ $req->status }}
                                    </span>
                                </div>
                            </div>
                            @if($req->remarks)
                                <div class="mt-2 ml-16 p-4 bg-white border border-slate-100 rounded-2xl text-[10px] text-slate-500 font-medium">
                                    <span class="font-black text-indigo-600 uppercase tracking-widest mr-2">Remarks:</span> {{ $req->remarks }}
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">No active requests</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- History of Requests -->
        <div class="bg-slate-50/50 rounded-[2.5rem] border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-100 bg-white/50">
                <h3 class="font-black text-slate-400 text-sm uppercase tracking-widest">History of Requests</h3>
            </div>
            <div class="p-8">
                @if(count($requestHistory) > 0)
                    <div class="space-y-3">
                        @foreach($requestHistory as $req)
                            <div class="flex items-center justify-between p-4 bg-white/50 rounded-2xl border border-slate-100 opacity-70 hover:opacity-100 transition-opacity">
                                <div class="flex items-center gap-4">
                                    <div class="text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-600">{{ $req->document_type }}</h4>
                                        <p class="text-[9px] font-medium text-slate-400">{{ $req->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest {{ $req->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                                    {{ $req->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">No past records</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-8 bg-indigo-50/50 rounded-[2rem] border border-indigo-100/50 flex items-center gap-6">
            <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight">Status Tracking</h4>
                <p class="text-[11px] text-slate-500 font-medium mt-1">Your requests are updated in real-time by the registrar's office. Check back here to see the progress of your applications.</p>
            </div>
        </div>
    </div>
</div>
@endsection
