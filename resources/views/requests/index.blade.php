@extends('layouts.app')

@section('title', 'Manage Requests')
@section('page_title', 'Document Requests')
@section('page_subtitle', 'Monitor and process student document applications')

@section('content')
<div class="space-y-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex flex-col gap-4 lg:flex-row lg:justify-between lg:items-center bg-slate-50">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-[#ffd22d]/20 rounded-lg border border-[#ffd22d]/60 flex items-center justify-center text-[#000638]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Active requests</h2>
                    <p class="text-sm text-slate-500 mt-1">{{ $requests->count() }} request(s) found</p>
                </div>
            </div>

            @if(auth()->user()->role === 'admin')
            <form action="{{ route('requests.reset-all') }}" method="POST" onsubmit="return confirm('CRITICAL ACTION: This will PERMANENTLY DELETE ALL requests, history, and ticket records. This cannot be undone. Are you absolutely sure?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Reset request system
                </button>
            </form>
            @endif
        </div>

        <div class="overflow-x-auto px-4 pb-4">
            <table class="w-full min-w-[1380px] table-fixed text-left">
                <colgroup>
                    <col class="w-[260px]">
                    <col class="w-[190px]">
                    <col class="w-[170px]">
                    <col class="w-[180px]">
                    <col class="w-[170px]">
                    <col class="w-[150px]">
                    <col class="w-[260px]">
                </colgroup>
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-5 py-4 text-xs font-bold uppercase tracking-wide text-slate-500">Ticket and student</th>
                        <th class="px-5 py-4 text-xs font-bold uppercase tracking-wide text-slate-500">Document type</th>
                        <th class="px-5 py-4 text-xs font-bold uppercase tracking-wide text-slate-500">Delivery and payment</th>
                        <th class="px-5 py-4 text-xs font-bold uppercase tracking-wide text-slate-500">Clearance</th>
                        <th class="px-5 py-4 text-xs font-bold uppercase tracking-wide text-slate-500">Current status</th>
                        <th class="px-5 py-4 text-xs font-bold uppercase tracking-wide text-slate-500">Request date</th>
                        <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($requests as $req)
                    <tr id="request-{{ $req->id }}" class="hover:bg-slate-50/50 transition-all group scroll-mt-6 target:bg-emerald-50 target:ring-2 target:ring-inset target:ring-emerald-400">
                        <td class="px-5 py-6 align-top">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-[#ffd22d]/20 rounded-2xl flex items-center justify-center text-[#000638] font-black text-xl group-hover:bg-[#000638] group-hover:text-[#ffd22d] transition-all">
                                    {{ substr($req->student->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] font-black bg-[#000638] text-white px-2 py-0.5 rounded-md uppercase tracking-widest">{{ $req->ticket_number ?? 'N/A' }}</span>
                                    </div>
                                    <div class="text-base font-black text-slate-800 tracking-tight">{{ $req->student->name }}</div>
                                    <div class="text-xs font-black text-[#000638] uppercase tracking-widest mt-1">{{ $req->student_number }}</div>
                                    @include('requests.partials.status-timeline', ['histories' => $req->statusHistories, 'staff' => true])
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-6 align-top text-sm font-bold leading-6 text-slate-700">
                            {{ $req->document_type }}
                            @if(in_array(strtolower($req->document_type), ['form 137', 'f137']) && $req->school_level)
                                <div class="mt-2 text-xs font-black uppercase tracking-wider text-blue-700">
                                    School level: {{ match($req->school_level) {
                                        'kinder', 'elementary' => 'Kinder and Elementary',
                                        'jhs' => 'Junior High School (JHS)',
                                        'shs' => 'Senior High School (SHS)',
                                        default => strtoupper($req->school_level),
                                    } }}
                                </div>
                            @endif
                            @if(in_array(strtolower($req->document_type), ['form 138', 'f138']) && $req->school_year)
                                <div class="mt-2 text-xs font-black uppercase tracking-wider text-blue-700">
                                    School year: {{ $req->school_year }}
                                </div>
                            @endif
                            @if($req->document_price !== null)
                                <div class="mt-2 inline-flex rounded-lg bg-[#ffd22d]/20 px-3 py-1 text-xs font-bold text-[#000638]">
                                    Fee: ₱{{ number_format($req->document_price, 2) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-6 align-top">
                            <div class="space-y-1">
                                <div class="text-xs font-black text-slate-700">
                                    <span class="text-[#000638]">Delivery:</span> {{ ucfirst($req->delivery_method ?? 'N/A') }}
                                </div>
                                <div class="text-xs font-black text-slate-700">
                                    <span class="text-[#000638]">Payment:</span> {{ ucfirst(str_replace('_', ' ', $req->payment_method ?? 'N/A')) }}
                                </div>
                                @if($req->release_location)
                                    <div class="text-xs font-medium text-slate-500 mt-1">
                                        {{ $req->release_location }}
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-6 align-top">
                            <div class="space-y-2">
                                <span class="inline-flex whitespace-nowrap px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider shadow-sm
                                    {{ $req->clearance_status == 'cleared' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : '' }}
                                    {{ $req->clearance_status == 'pending_clearance' ? 'bg-amber-50 text-amber-700 border border-amber-100' : '' }}
                                    {{ $req->clearance_status == 'has_balance' ? 'bg-red-50 text-red-700 border border-red-100' : '' }}
                                    {{ !$req->clearance_status ? 'bg-slate-100 text-slate-600 border border-slate-200' : '' }}
                                ">
                                    {{ $req->clearance_status ? str_replace('_', ' ', $req->clearance_status) : 'Not Checked' }}
                                </span>
                                @if($req->financial_balance > 0)
                                    <div class="text-xs font-black text-red-600">
                                        ₱{{ number_format($req->financial_balance, 2) }}
                                    </div>
                                @endif
                                <span class="inline-flex whitespace-nowrap px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest
                                    {{ $req->payment_confirmed ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}
                                ">
                                    {{ $req->payment_confirmed ? 'Paid' : 'Unpaid' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-6 align-top">
                            <span class="inline-flex whitespace-nowrap px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider shadow-sm
                                {{ $req->status == 'pending' ? 'bg-amber-50 text-amber-700 border border-amber-100' : '' }}
                                {{ $req->status == 'processing' ? 'bg-blue-50 text-blue-700 border border-blue-100' : '' }}
                                {{ $req->status == 'processed' ? 'bg-purple-50 text-purple-700 border border-purple-100' : '' }}
                                {{ $req->status == 'ready_to_release' ? 'bg-[#ffd22d]/20 text-[#000638] border border-[#ffd22d]/60' : '' }}
                                {{ $req->status == 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : '' }}
                            ">
                                {{ str_replace('_', ' ', $req->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-6 align-top">
                            <div class="text-sm font-bold text-slate-800">{{ $req->created_at->format('M d, Y') }}</div>
                            <div class="text-xs font-black text-slate-500 uppercase tracking-widest mt-1">{{ $req->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="px-5 py-6 align-top">
                            <div class="flex items-center justify-end gap-3">
                                @if(in_array(auth()->user()->role, ['registrar', 'admin']) && $req->status === 'processed')
                                         <div class="flex w-full flex-col gap-3">
                                             {{-- Preview Buttons (Direct PDF) --}}
                                             @if(str_starts_with($req->document_type, 'Certificate of '))
                                                <a href="{{ route('certifications.index', ['request_id' => $req->id]) }}" class="w-full px-4 py-2.5 bg-[#062b63] text-white rounded-xl text-xs font-black uppercase tracking-wider hover:bg-[#041d45] transition-all text-center">
                                                    Open Certification Maker
                                                </a>
                                             @elseif(str_contains(strtolower($req->document_type), 'good moral'))
                                                <a href="{{ route('good-moral.preview-request', $req->id) }}" target="_blank" class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-100 transition-all text-center border border-indigo-100">
                                                    Preview Good Moral
                                                </a>
                                             @elseif(str_contains(strtolower($req->document_type), 'diploma'))
                                                @php
                                                    $isPickup = str_contains(strtoupper($req->remarks ?? ''), 'MODE: PICKUP');
                                                @endphp
                                                @if(!$isPickup)
                                                    <a href="{{ route('diploma.preview-request', $req->id) }}" target="_blank" class="px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-amber-100 transition-all text-center border border-amber-100">
                                                        Preview Diploma
                                                    </a>
                                                @else
                                                    <span class="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-black uppercase tracking-widest text-center border border-emerald-100">
                                                        For Pickup
                                                    </span>
                                                @endif
                                             @elseif(in_array(strtolower($req->document_type), ['form 137', 'form 138', 'f137', 'f138']))
                                                <a href="{{ route('generator.maker', ['documentRequest' => $req->id, 'form' => str_contains(strtolower($req->document_type), '137') ? 'f137' : 'f138']) }}" target="_blank" class="px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-blue-100 transition-all text-center border border-blue-100">
                                                    Open {{ $req->document_type }} Maker
                                                </a>
                                             @endif
                                             
                                             <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="space-y-2">
                                                 @csrf
                                                 <input type="text" name="remarks" placeholder="Add remarks (optional)" class="text-sm font-bold bg-white border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 w-full" value="{{ $req->remarks }}">
                                                 <div class="flex gap-2">
                                                     <button type="submit" name="status" value="ready_to_release" class="flex-1 px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition-all">
                                                         Approve
                                                     </button>
                                                     <button type="submit" name="status" value="rejected" class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-red-500/20 hover:bg-red-700 transition-all" onclick="return confirm('Are you sure?')">
                                                         Reject
                                                     </button>
                                                 </div>
                                             </form>
                                         </div>
                                @elseif(in_array(auth()->user()->role, ['registrar', 'admin']) && $req->status === 'ready_to_release')
                                    <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="w-full">
                                        @csrf
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white hover:bg-emerald-700" onclick="return confirm('Mark ticket {{ $req->ticket_number }} as released?')">Mark as Released</button>
                                    </form>
                                @elseif(in_array(auth()->user()->role, ['records_officer', 'admin']) && $transitions->allowed($req, auth()->user()) !== [])
                                    <div class="flex flex-col gap-2 w-full">
                                        <!-- Payment & Clearance Actions -->
                                        <div class="flex flex-col gap-2">
                                            @if($req->payment_proof_path)
                                                <a href="{{ route('requests.receipt', $req) }}" target="_blank" rel="noopener noreferrer" class="px-3 py-2 bg-indigo-600 text-white text-xs font-black rounded-xl hover:bg-indigo-700 transition-all uppercase tracking-widest text-center">
                                                    View Transcript Receipt
                                                </a>
                                            @endif
                                            <div class="flex gap-2">
                                                @if(!$req->payment_confirmed)
                                                    <form action="{{ route('requests.confirm-payment', $req->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-2 bg-emerald-600 text-white text-xs font-black rounded-xl hover:bg-emerald-700 transition-all uppercase tracking-widest" title="Confirm Payment">
                                                            Confirm Payment
                                                        </button>
                                                    </form>
                                                @endif
                                            
                                            @if(in_array(auth()->user()->role, ['registrar', 'admin']))
                                                <button type="button" onclick="toggleClearanceForm({{ $req->id }})" class="px-3 py-2 bg-amber-600 text-white text-xs font-black rounded-xl hover:bg-amber-700 transition-all uppercase tracking-widest" title="Update Clearance">
                                                    Clearance
                                                </button>
                                            @endif
                                        </div>
                                        
                                        <!-- Status Update -->
                                        <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="flex flex-col gap-2">
                                            @csrf
                                            <div class="flex items-center gap-2">
                                                <select name="status" class="text-xs font-black uppercase tracking-widest bg-white border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm min-w-[160px]">
                                                    <option value="{{ $req->status }}" selected>Current: {{ str_replace('_', ' ', ucfirst($req->status)) }}</option>
                                                    @foreach($transitions->allowed($req, auth()->user()) as $target)
                                                        <option value="{{ $target }}">{{ str_replace('_', ' ', ucfirst($target)) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="w-12 h-12 flex items-center justify-center bg-[#000638] text-white rounded-xl shadow-sm hover:bg-[#10175a] transition-all shrink-0" title="Update Status">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <input type="text" name="remarks" placeholder="Add remarks..." class="text-sm font-bold bg-white border-slate-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm" value="{{ $req->remarks }}">
                                        </form>
                                        
                                        <!-- Clearance Update Form (Hidden) -->
                                        <div id="clearance-form-{{ $req->id }}" style="display: none;" class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                                            <form action="{{ route('requests.update-clearance', $req->id) }}" method="POST">
                                                @csrf
                                                <div class="flex flex-col gap-2">
                                                    <select name="clearance_status" class="text-xs font-bold bg-white border-amber-300 rounded-lg px-3 py-2">
                                                        <option value="cleared" {{ $req->clearance_status == 'cleared' ? 'selected' : '' }}>Cleared</option>
                                                        <option value="pending_clearance" {{ $req->clearance_status == 'pending_clearance' ? 'selected' : '' }}>Pending Clearance</option>
                                                        <option value="has_balance" {{ $req->clearance_status == 'has_balance' ? 'selected' : '' }}>Has Balance</option>
                                                    </select>
                                                    <input type="number" name="financial_balance" value="{{ $req->financial_balance }}" placeholder="Balance (₱)" step="0.01" min="0" class="text-xs font-bold bg-white border-amber-300 rounded-lg px-3 py-2">
                                                    <button type="submit" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-black rounded-lg hover:bg-amber-700 transition-all">
                                                        Update Clearance
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    @if(str_starts_with($req->document_type, 'Certificate of '))
                                    <a href="{{ route('certifications.index', ['request_id' => $req->id]) }}" class="w-12 h-12 flex items-center justify-center bg-[#062b63] text-white rounded-xl shadow-lg transition-all hover:bg-[#041d45]" title="Open Certification Maker">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414A1 1 0 0118 9.414V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                    @elseif(str_contains(strtolower($req->document_type), 'good moral'))
                                    <a href="{{ route('good-moral.index', ['request_id' => $req->id]) }}" class="w-12 h-12 flex items-center justify-center bg-indigo-500 text-white rounded-xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-600 transition-all" title="Open Good Moral Maker">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </a>
                                    @endif

                                    @if(in_array(strtolower($req->document_type), ['form 137', 'form 138', 'f137', 'f138']))
                                    <a href="{{ route('generator.maker', ['documentRequest' => $req->id, 'form' => str_contains(strtolower($req->document_type), '137') ? 'f137' : 'f138']) }}" target="_blank" class="w-12 h-12 flex items-center justify-center bg-blue-600 text-white rounded-xl shadow-lg hover:bg-blue-700 transition-all" title="Open {{ $req->document_type }} Maker">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0118 9.414V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                    @endif

                                    @if(str_contains(strtolower($req->document_type), 'diploma'))
                                    <a href="{{ route('diploma.index', ['request_id' => $req->id]) }}" class="w-12 h-12 flex items-center justify-center bg-amber-500 text-white rounded-xl shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all" title="Open Diploma Maker">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <div class="p-16 text-center bg-slate-50/30">
                    <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center text-slate-300 mx-auto mb-6 border border-slate-200">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0l-8 4-8-4" />
                        </svg>
                    </div>
                    <h3 class="text-slate-700 text-lg font-semibold">No requests yet</h3>
                    <p class="text-slate-500 mt-2">All document applications will appear here.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function toggleClearanceForm(requestId) {
    const form = document.getElementById('clearance-form-' + requestId);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
</script>
@endsection
