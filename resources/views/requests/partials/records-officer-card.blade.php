@php
    $allowedStatuses = $transitions->allowed($req, auth()->user());
    $nextStep = match ($req->status) {
        'pending' => 'Start processing after checking the request details.',
        'processing' => 'Prepare the document, then send it to the registrar for review.',
        'processed' => 'Sent for registrar review. Return to processing only if a correction is needed.',
        'ready_to_release' => 'The registrar is handling release. Return it for correction only if needed.',
        default => 'Review the request details below.',
    };
    $documentName = strtolower($req->document_type);
    $hasPreparedDocument = $req->hasPreparedDocument();
    $accent = match ($req->status) {
        'pending' => 'border-l-amber-300',
        'processing' => 'border-l-sky-300',
        'processed' => 'border-l-emerald-300',
        default => 'border-l-slate-300',
    };
@endphp

<article id="request-{{ $req->id }}" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200 border-l-4 {{ $accent }} bg-white shadow-sm transition-shadow hover:shadow-md target:ring-2 target:ring-emerald-400" aria-label="Request {{ $req->ticket_number ?? '#'.$req->id }}">
    <div class="border-b border-slate-200 bg-white px-5 py-5 sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold tracking-wide text-slate-700">Ticket {{ $req->ticket_number ?? '#'.$req->id }}</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#eef1f8] text-lg font-bold text-[#000638]">{{ strtoupper(mb_substr($req->student->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <h3 class="break-words text-lg font-bold leading-6 text-[#000638]">{{ $req->student->name }}</h3>
                        <p class="mt-0.5 text-xs text-slate-600">Student no. {{ $req->student_number }}</p>
                    </div>
                </div>
            </div>
            <div class="shrink-0 text-right">
                <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">Current status</p>
                <x-request-status-badge :status="$req->status" />
            </div>
        </div>
        <p class="mt-4 text-xs text-slate-500">Requested {{ $req->created_at->format('M d, Y · h:i A') }}</p>
    </div>

    <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Requested document</p>
            <p class="mt-1 text-base font-bold text-[#000638]">{{ $req->document_type }}</p>
            @if($req->school_year)<p class="mt-1 text-sm text-slate-600">School year: {{ $req->school_year }}</p>@endif
            @if($req->school_level)<p class="mt-1 text-sm text-slate-600">School level: {{ strtoupper($req->school_level) }}</p>@endif
            @if($req->document_price !== null)<p class="mt-1 text-sm text-slate-600">Document fee: ₱{{ number_format($req->document_price, 2) }}</p>@endif
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Delivery and payment</p>
            <p class="mt-1 text-sm text-slate-700">Delivery: <strong>{{ ucfirst($req->delivery_method ?? 'Not set') }}</strong></p>
            <p class="mt-1 text-sm text-slate-700">Payment method: <strong>{{ ucfirst(str_replace('_', ' ', $req->payment_method ?? 'Not set')) }}</strong></p>
            @if($req->release_location)<p class="mt-1 text-sm text-slate-600">Release at: {{ $req->release_location }}</p>@endif
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Accounting check</p>
            <p class="mt-1 text-sm text-slate-700">Payment: <strong class="{{ $req->payment_confirmed ? 'text-emerald-700' : 'text-amber-700' }}">{{ $req->payment_confirmed ? 'Confirmed' : 'Not yet confirmed' }}</strong></p>
            <p class="mt-1 text-sm text-slate-700">Clearance: <strong>{{ $req->clearance_status ? ucfirst(str_replace('_', ' ', $req->clearance_status)) : 'Not checked' }}</strong></p>
            @if($req->financial_balance > 0)<p class="mt-1 text-sm font-semibold text-red-700">Balance: ₱{{ number_format($req->financial_balance, 2) }}</p>@endif
            <p class="mt-2 text-xs text-slate-500">Payment and clearance are updated by the registrar or admin.</p>
        </div>
    </div>

    <div class="grid gap-4 border-t border-slate-200 bg-white px-4 pb-5 pt-1 sm:px-5">
        <div class="pt-1">@include('requests.partials.status-timeline', ['histories' => $req->statusHistories, 'staff' => true])</div>
        <div class="order-2 border-t border-slate-200 px-1 pt-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#000638]">Document tools</p>
            <p class="mt-1 text-sm text-slate-600">Generate and download the protected document before forwarding it to the registrar.</p>
            @if(str_starts_with($req->document_type, 'Certificate of '))
                <a href="{{ route('certifications.index', ['request_id' => $req->id]) }}" class="mt-3 inline-flex rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638] hover:bg-slate-100">Open Certification Maker</a>
            @elseif(str_contains($documentName, 'good moral'))
                <a href="{{ route('good-moral.index', ['request_id' => $req->id]) }}" class="mt-3 inline-flex rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638] hover:bg-slate-100">Open Good Moral Maker</a>
            @elseif(in_array($documentName, ['form 137', 'form 138', 'f137', 'f138']))
                @php
                    $schoolForm = str_contains($documentName, '137') ? 'f137' : 'f138';
                @endphp
                <a href="{{ route('school-forms.'.$schoolForm.'.preview', array_filter(['student' => $req->student_number, 'school_year' => $schoolForm === 'f138' ? $req->school_year : null, 'request_id' => $req->id])) }}" class="mt-3 inline-flex rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638] hover:bg-slate-100">Continue processing {{ $req->document_type }}</a>
            @elseif(str_contains($documentName, 'diploma'))
                <a href="{{ route('diploma.index', ['request_id' => $req->id]) }}" class="mt-3 inline-flex rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638] hover:bg-slate-100">Open Diploma Maker</a>
            @endif
            @if($hasPreparedDocument)
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('requests.document-preview', $req) }}" target="_blank" rel="noopener" class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Preview generated document</a>
                    <a href="{{ route('requests.document-download', $req) }}" class="inline-flex rounded-lg bg-[#000638] px-4 py-2 text-sm font-bold text-white hover:bg-[#10175a]">Download generated document</a>
                </div>
            @endif
        </div>
        <div class="order-1 rounded-xl border border-slate-200 bg-[#f8f9fc] p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-[#000638]">Next step</p>
            <p class="mt-1 text-sm text-slate-600">{{ $nextStep }}</p>
            @if($allowedStatuses !== [])
                <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="mt-3"
                      data-request-identifier="{{ $req->ticket_number ?? '#'.$req->id }}"
                      onsubmit="const status=event.submitter?.value; const id=this.dataset.requestIdentifier; const remarks=this.querySelector('[name=remarks]'); if(status==='rejected' && !remarks.value.trim()){remarks.setCustomValidity('Enter the reason for rejecting this request.');remarks.reportValidity();return false;} remarks.setCustomValidity(''); if (status === 'rejected') return confirm('Reject request ' + id + '? The student will see it as rejected. This cannot be undone through Request Management.'); if (status === 'completed') return confirm('Mark request ' + id + ' as released? This cannot be undone through Request Management.'); return true;">
                    @csrf
                    <label for="status-remarks-{{ $req->id }}" class="block text-xs font-semibold text-slate-700">Remarks (required when rejecting)</label>
                    <input id="status-remarks-{{ $req->id }}" type="text" name="remarks" value="{{ $req->remarks }}" placeholder="Add a reason or update, if needed" oninput="this.setCustomValidity('')" class="mt-1 w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#000638] focus:ring-[#ffd22d]">
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($allowedStatuses as $target)
                            @php
                                $actionLabel = match ($target) {
                                    'processing' => $req->status === 'pending' ? 'Start processing' : 'Return to processing',
                                    'processed' => $req->status === 'processing' ? 'Downloaded - forward to registrar' : 'Return for registrar review',
                                    'rejected' => 'Reject request',
                                    default => ucfirst(str_replace('_', ' ', $target)),
                                };
                            @endphp
                            <button type="submit" name="status" value="{{ $target }}" class="rounded-lg px-4 py-2.5 text-sm font-bold shadow-sm {{ $target === 'rejected' ? 'border border-red-200 bg-white text-red-700 hover:bg-red-50' : 'bg-[#000638] text-white hover:bg-[#10175a]' }}">{{ $actionLabel }}</button>
                        @endforeach
                    </div>
                </form>
            @else
                <p class="mt-3 rounded-lg bg-white px-4 py-3 text-sm text-slate-600">No status change is available for the records officer at this stage.</p>
            @endif
            @if($req->status === 'processing' && ! $hasPreparedDocument)
                <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">The forwarding button will appear after a protected PDF has been generated and downloaded.</p>
            @endif
        </div>
    </div>
</article>
