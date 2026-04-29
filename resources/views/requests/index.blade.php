@extends('layouts.app')

@section('title', 'Manage Requests')
@section('page_title', 'Document Requests')
@section('page_subtitle', 'Monitor and process student document applications')

@section('content')
<div class="space-y-10">
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-10 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-indigo-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Active Requests</h2>
                    <p class="text-sm font-medium text-slate-400 mt-1">Total of {{ $requests->count() }} request(s) found</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                        <th class="px-10 py-6">Student Information</th>
                        <th class="px-10 py-6">Document Type</th>
                        <th class="px-10 py-6">Current Status</th>
                        <th class="px-10 py-6">Request Date</th>
                        <th class="px-10 py-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($requests as $req)
                    <tr class="hover:bg-slate-50/50 transition-all group">
                        <td class="px-10 py-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 font-black text-lg group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    {{ substr($req->student->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-black text-slate-800 tracking-tight">{{ $req->student->name }}</div>
                                    <div class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mt-1">{{ $req->student_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-8 text-sm font-bold text-slate-600">
                            {{ $req->document_type }}
                        </td>
                        <td class="px-10 py-8">
                            <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm
                                {{ $req->status == 'pending' ? 'bg-amber-50 text-amber-600' : '' }}
                                {{ $req->status == 'processing' ? 'bg-blue-50 text-blue-600' : '' }}
                                {{ $req->status == 'processed' ? 'bg-purple-50 text-purple-600' : '' }}
                                {{ $req->status == 'ready_to_release' ? 'bg-indigo-50 text-indigo-600' : '' }}
                                {{ $req->status == 'completed' ? 'bg-emerald-50 text-emerald-600' : '' }}
                            ">
                                {{ str_replace('_', ' ', $req->status) }}
                            </span>
                        </td>
                        <td class="px-10 py-8">
                            <div class="text-sm font-bold text-slate-800">{{ $req->created_at->format('M d, Y') }}</div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ $req->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="px-10 py-8">
                            <div class="flex items-center justify-end gap-3">
                                @if(in_array(auth()->user()->role, ['registrar', 'admin']) && $req->status === 'processed')
                                         <div class="flex flex-col gap-3">
                                             {{-- Preview Buttons (Direct PDF) --}}
                                             @if(str_contains(strtolower($req->document_type), 'good moral'))
                                                <a href="{{ route('good-moral.preview-request', $req->id) }}" target="_blank" class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-all text-center">
                                                    Preview Good Moral
                                                </a>
                                             @elseif(str_contains(strtolower($req->document_type), 'diploma'))
                                                @php
                                                    $isPickup = str_contains(strtoupper($req->remarks ?? ''), 'MODE: PICKUP');
                                                @endphp
                                                @if(!$isPickup)
                                                    <a href="{{ route('diploma.preview-request', $req->id) }}" target="_blank" class="px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-100 transition-all text-center">
                                                        Preview Diploma
                                                    </a>
                                                @else
                                                    <span class="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-[10px] font-black uppercase tracking-widest text-center">
                                                        For Pickup
                                                    </span>
                                                @endif
                                             @elseif(str_contains(strtolower($req->document_type), '137'))
                                                <a href="{{ route('form-137.preview-request', $req->id) }}" target="_blank" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all text-center">
                                                    Preview F137
                                                </a>
                                             @endif
                                             
                                             <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="space-y-2">
                                                 @csrf
                                                 <input type="text" name="remarks" placeholder="Add remarks (optional)" class="text-[10px] font-bold bg-white border-slate-200 rounded-lg px-3 py-1.5 focus:ring-1 focus:ring-indigo-500 w-full" value="{{ $req->remarks }}">
                                                 <div class="flex gap-2">
                                                     <button type="submit" name="status" value="ready_to_release" class="flex-1 px-3 py-2 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition-all">
                                                         Approve
                                                     </button>
                                                     <button type="submit" name="status" value="rejected" class="flex-1 px-3 py-2 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-500/20 hover:bg-red-700 transition-all" onclick="return confirm('Are you sure?')">
                                                         Reject
                                                     </button>
                                                 </div>
                                             </form>
                                         </div>
                                @elseif(in_array(auth()->user()->role, ['records_officer', 'admin']))
                                    <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="flex flex-col gap-2">
                                        @csrf
                                        <div class="flex items-center gap-2">
                                            <select name="status" class="text-[10px] font-black uppercase tracking-widest bg-white border-slate-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm min-w-[140px]">
                                                <option value="pending" {{ $req->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="processing" {{ $req->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                                <option value="processed" {{ $req->status == 'processed' ? 'selected' : '' }}>Processed</option>
                                                <option value="ready_to_release" {{ $req->status == 'ready_to_release' ? 'selected' : '' }}>Ready to Release</option>
                                                <option value="completed" {{ $req->status == 'completed' ? 'selected' : '' }}>Released</option>
                                                <option value="rejected" {{ $req->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            </select>
                                            <button type="submit" class="w-10 h-10 flex items-center justify-center bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all shrink-0" title="Update Status">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" name="remarks" placeholder="Add remarks..." class="text-[10px] font-bold bg-white border-slate-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm" value="{{ $req->remarks }}">
                                    </form>

                                    @if($req->document_type == 'Form 137')
                                    <a href="{{ route('form-137.index', ['student_number' => $req->student_number]) }}" class="w-10 h-10 flex items-center justify-center bg-amber-500 text-white rounded-xl shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all" title="Open F137 Maker">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    @endif

                                    @if(str_contains(strtolower($req->document_type), 'good moral'))
                                    <a href="{{ route('good-moral.index', ['request_id' => $req->id]) }}" class="w-10 h-10 flex items-center justify-center bg-indigo-500 text-white rounded-xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-600 transition-all" title="Open Good Moral Maker">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </a>
                                    @endif

                                    @if(str_contains(strtolower($req->document_type), 'diploma'))
                                    <a href="{{ route('diploma.index', ['request_id' => $req->id]) }}" class="w-10 h-10 flex items-center justify-center bg-amber-500 text-white rounded-xl shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all" title="Open Diploma Maker">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                        </svg>
                                    </a>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($requests->isEmpty())
                <div class="p-32 text-center bg-slate-50/30">
                    <div class="w-24 h-24 bg-white rounded-[2rem] flex items-center justify-center text-slate-200 mx-auto mb-8 shadow-sm border border-slate-100">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0l-8 4-8-4" />
                        </svg>
                    </div>
                    <h3 class="text-slate-400 text-lg font-black uppercase tracking-[0.3em]">No requests yet</h3>
                    <p class="text-slate-300 font-medium mt-3">All document applications will appear here</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
