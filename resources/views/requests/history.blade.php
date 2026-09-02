@extends('layouts.app')

@section('title', 'Request History')
@section('page_title', 'Request History')
@section('page_subtitle', 'Archive of all completed and rejected document requests')

@section('content')
<div class="space-y-10">
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-10 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Records Archive</h2>
                    <p class="text-sm font-medium text-slate-400 mt-1">Total of {{ $requests->count() }} history record(s) found</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                @if(auth()->user()->role === 'admin')
                <form action="{{ route('requests.reset-all') }}" method="POST" onsubmit="return confirm('CRITICAL ACTION: This will PERMANENTLY DELETE ALL requests, history, and ticket records. This cannot be undone. Are you absolutely sure?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-3 bg-red-600 text-white text-xs font-black rounded-xl shadow-lg shadow-red-500/20 hover:bg-red-700 transition-all uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Full System Reset
                    </button>
                </form>
                @endif
                
                @if($requests->count() > 0)
                <button type="button" onclick="toggleClearModal()" class="px-6 py-3 bg-red-50 text-red-600 hover:bg-red-100 font-bold text-xs uppercase tracking-widest rounded-xl transition-all">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Clear History
                </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                        <th class="px-10 py-6">Ticket & Student</th>
                        <th class="px-10 py-6">Verification</th>
                        <th class="px-10 py-6">Document Type</th>
                        <th class="px-10 py-6">Status</th>
                        <th class="px-10 py-6">Date Processed</th>
                        <th class="px-10 py-6">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($requests as $req)
                    @php($authenticity = $req->authenticities->first())
                    <tr class="hover:bg-slate-50/50 transition-all group opacity-80 hover:opacity-100">
                        <td class="px-10 py-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 font-black text-lg">
                                    {{ substr($req->student->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[9px] font-black bg-slate-200 text-slate-500 px-2 py-0.5 rounded-md uppercase tracking-widest">{{ $req->ticket_number ?? 'N/A' }}</span>
                                    </div>
                                    <div class="text-sm font-black text-slate-800 tracking-tight">{{ $req->student->name }}</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ $req->student_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-8">
                            @if($authenticity)
                                <div class="text-[10px] font-black text-slate-700">{{ $authenticity->control_number }}</div>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <a href="{{ app(\App\Support\DocumentQrCode::class)->verificationUrl($authenticity) }}" target="_blank" class="text-[10px] font-black uppercase tracking-wider text-indigo-600">Open result</a>
                                    <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase {{ $authenticity->status === 'valid' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">{{ $authenticity->status }}</span>
                                </div>
                                @if($authenticity->status !== 'revoked' && in_array(auth()->user()->role, ['admin', 'registrar']))
                                    <form class="mt-2" method="POST" action="{{ route('documents.revoke', $authenticity) }}" onsubmit="return confirm('Revoke this issued document? The QR will show a warning immediately.');">
                                        @csrf
                                        <input type="hidden" name="reason" value="Revoked by {{ auth()->user()->role }} from request history">
                                        <button class="text-[9px] font-black uppercase tracking-wider text-red-600" type="submit">Revoke document</button>
                                    </form>
                                @endif
                            @else
                                <span class="text-[10px] text-slate-400">Not issued digitally</span>
                            @endif
                        </td>
                        <td class="px-10 py-8 text-sm font-bold text-slate-600">
                            {{ $req->document_type }}
                        </td>
                        <td class="px-10 py-8">
                            <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm
                                {{ $req->status == 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}
                            ">
                                {{ str_replace('_', ' ', $req->status) }}
                            </span>
                        </td>
                        <td class="px-10 py-8">
                            <div class="text-sm font-bold text-slate-800">{{ $req->updated_at->format('M d, Y') }}</div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ $req->updated_at->format('h:i A') }}</div>
                        </td>
                        <td class="px-10 py-8 text-xs text-slate-400 italic">
                            {{ $req->remarks ?? 'No remarks' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($requests->isEmpty())
                <div class="p-32 text-center bg-slate-50/30">
                    <h3 class="text-slate-400 text-lg font-black uppercase tracking-[0.3em]">No history yet</h3>
                </div>
            @endif
        </div>
    </div>
</div>

<div id="clearModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-10 max-w-lg w-full mx-4 shadow-2xl">
        <div class="text-center">
            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-2">Clear Request History?</h3>
            <p class="text-slate-500 mb-8">This action cannot be undone. All selected records will be permanently deleted.</p>
        </div>
        <form id="clearForm" method="POST" action="{{ route('requests.history.clear') }}">
            @csrf
            @method('DELETE')
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-3">Select what to delete:</label>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-all">
                        <input type="radio" name="action" value="completed" class="w-5 h-5 text-red-600 focus:ring-red-500" required>
                        <span class="text-sm font-bold text-slate-700">Completed requests only</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-all">
                        <input type="radio" name="action" value="rejected" class="w-5 h-5 text-red-600 focus:ring-red-500">
                        <span class="text-sm font-bold text-slate-700">Rejected requests only</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition-all">
                        <input type="radio" name="action" value="all" class="w-5 h-5 text-red-600 focus:ring-red-500">
                        <span class="text-sm font-bold text-slate-700">All history (completed & rejected)</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-4">
                <button type="button" onclick="toggleClearModal()" class="flex-1 px-6 py-4 bg-slate-100 text-slate-700 font-bold text-sm uppercase tracking-widest rounded-xl hover:bg-slate-200 transition-all">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-6 py-4 bg-red-600 text-white font-bold text-sm uppercase tracking-widest rounded-xl hover:bg-red-700 transition-all">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleClearModal() {
    const modal = document.getElementById('clearModal');
    modal.classList.toggle('hidden');
}
</script>
@endsection
