@php
    $detailId = 'request-details-'.$req->id;
    $formatValue = fn ($value) => filled($value) ? $value : 'Not provided';
    $purposeLabel = match ($req->request_purpose) {
        'transfer_enrollment' => 'Transfer / Enrollment',
        'employment' => 'Employment',
        'other' => 'Other Valid Purpose',
        default => $formatValue($req->request_purpose),
    };
    $allowedStatuses = isset($transitions) ? $transitions->allowed($req, auth()->user()) : [];
    $documentName = strtolower($req->document_type);
@endphp

<dialog id="{{ $detailId }}" class="m-auto max-h-[97vh] w-[min(1280px,calc(100%-1rem))] overflow-hidden rounded-3xl border-0 p-0 shadow-2xl backdrop:bg-slate-950/75 backdrop:backdrop-blur-sm">
    <div class="flex max-h-[95vh] flex-col bg-white">
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-white/10 bg-[#000638] px-6 py-5 text-white sm:px-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#ffd22d]">Review request</p>
                <h3 class="mt-1 text-xl font-black">{{ $req->ticket_number ?? 'Request #'.$req->id }}</h3>
                <p class="mt-1 text-sm text-slate-200">{{ $req->document_type }} · {{ $req->student->name }}</p>
            </div>
            <button type="button" onclick="document.getElementById('{{ $detailId }}').close()" class="rounded-lg p-2 text-2xl leading-none text-white hover:bg-white/10" aria-label="Close request details">&times;</button>
        </header>

        <div class="overflow-y-auto bg-slate-100 p-4 sm:p-6">
            <section class="mb-4 rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 via-white to-amber-50 p-4 shadow-sm sm:p-5 {{ in_array(auth()->user()->role, ['registrar', 'records_officer'], true) ? 'hidden' : '' }}" aria-label="Request summary">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h4 class="text-sm font-black uppercase tracking-wide text-[#000638]">At a glance</h4>
                    <x-request-status-badge :status="$req->status" />
                </div>
                <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border border-blue-100 bg-white p-3"><dt class="text-[11px] font-bold uppercase tracking-wide text-blue-600">Document</dt><dd class="mt-1 text-base font-black text-[#000638]">{{ $req->document_type }}</dd></div>
                    <div class="rounded-xl border border-blue-100 bg-white p-3"><dt class="text-[11px] font-bold uppercase tracking-wide text-blue-600">Student</dt><dd class="mt-1 text-sm font-black text-slate-900">{{ $req->student->name }}</dd><dd class="mt-0.5 text-xs font-bold text-slate-500">{{ $req->student_number }}</dd></div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3"><dt class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Purpose</dt><dd class="mt-1 text-sm font-black text-amber-950">{{ $purposeLabel }}</dd></div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3"><dt class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">Destination</dt><dd class="mt-1 text-sm font-black text-emerald-950">{{ $formatValue($req->receiving_school_name) }}</dd></div>
                    <div class="rounded-xl border border-violet-200 bg-violet-50 p-3"><dt class="text-[11px] font-bold uppercase tracking-wide text-violet-700">Submitted</dt><dd class="mt-1 text-sm font-black text-violet-950">{{ $req->created_at->format('M d, Y') }}</dd><dd class="mt-0.5 text-xs font-bold text-violet-700">{{ $req->created_at->format('h:i A') }}</dd></div>
                </dl>
            </section>

            @if(in_array(auth()->user()->role, ['registrar', 'records_officer'], true))
            <section class="mb-4 rounded-2xl border-2 border-[#000638] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                    <p class="text-xs font-black uppercase tracking-wider text-blue-700">{{ auth()->user()->role === 'registrar' ? 'Quick review' : 'Request summary' }}</p>
                    <h4 class="mt-1 text-xl font-black text-[#000638]">{{ auth()->user()->role === 'registrar' ? 'Check these details before deciding' : 'Process this document request' }}</h4>
                    <p class="mt-1 text-sm text-slate-600">{{ auth()->user()->role === 'registrar' ? 'Everything needed for approval is shown here. No technical fields are required.' : 'The key details are shown here. Use the action panel below to process and forward the document.' }}</p>
                    </div>
                    <x-request-status-badge :status="$req->status" />
                </div>
                <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-blue-50 p-4"><dt class="text-xs font-bold text-blue-700">Student</dt><dd class="mt-1 font-black text-slate-950">{{ $req->student->name }}</dd><dd class="text-xs text-slate-600">{{ $req->student_number }}</dd></div>
                    <div class="rounded-xl bg-violet-50 p-4"><dt class="text-xs font-bold text-violet-700">Document requested</dt><dd class="mt-1 font-black text-slate-950">{{ $req->document_type }}</dd><dd class="text-xs text-slate-600">School year: {{ $formatValue($req->school_year) }}</dd></div>
                    <div class="rounded-xl bg-amber-50 p-4"><dt class="text-xs font-bold text-amber-700">Purpose</dt><dd class="mt-1 font-black text-slate-950">{{ $purposeLabel }}</dd><dd class="text-xs text-slate-600">{{ $formatValue($req->purpose_details) }}</dd></div>
                    <div class="rounded-xl bg-emerald-50 p-4"><dt class="text-xs font-bold text-emerald-700">Destination</dt><dd class="mt-1 font-black text-slate-950">{{ $formatValue($req->receiving_school_name) }}</dd><dd class="text-xs text-slate-600">{{ $formatValue($req->receiving_school_address) }}</dd></div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                    <span class="rounded-full bg-slate-100 px-3 py-1.5">Ticket: {{ $formatValue($req->ticket_number) }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5">Submitted: {{ $req->created_at->format('M d, Y h:i A') }}</span>
                </div>
            </section>
            @endif
            <div class="grid gap-4 lg:grid-cols-2">
                @if(!in_array(auth()->user()->role, ['registrar', 'records_officer'], true))
                <section class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                    <h4 class="flex items-center gap-2 text-sm font-black text-[#000638]"><span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-100 text-blue-700">01</span> Student information</h4>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-blue-50 p-3"><dt class="text-xs font-bold uppercase text-blue-600">Full name</dt><dd class="mt-1 text-base font-black text-[#000638]">{{ $req->student->name }}</dd></div>
                        <div class="rounded-lg bg-blue-50 p-3"><dt class="text-xs font-bold uppercase text-blue-600">Student Number</dt><dd class="mt-1 text-base font-black text-[#000638]">{{ $req->student_number }}</dd></div>
                        <div><dt class="text-slate-500">LRN</dt><dd class="font-bold text-slate-900">{{ $formatValue($req->student->lrn) }}</dd></div>
                        <div><dt class="text-slate-500">Official email</dt><dd class="break-all font-bold text-slate-900">{{ $formatValue($req->student->official_email) }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">
                    <h4 class="flex items-center gap-2 text-sm font-black text-[#000638]"><span class="grid h-8 w-8 place-items-center rounded-lg bg-violet-100 text-violet-700">02</span> Request information</h4>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-violet-50 p-3"><dt class="text-xs font-bold uppercase text-violet-600">Ticket number</dt><dd class="mt-1 text-base font-black text-violet-950">{{ $formatValue($req->ticket_number) }}</dd></div>
                        <div class="rounded-lg bg-violet-50 p-3"><dt class="text-xs font-bold uppercase text-violet-600">Document</dt><dd class="mt-1 text-base font-black text-violet-950">{{ $req->document_type }}</dd></div>
                        <div><dt class="text-slate-500">Request type</dt><dd class="font-bold">{{ $req->requestType?->name ?? 'Built-in document' }}</dd></div>
                        <div><dt class="text-slate-500">School year</dt><dd class="font-bold">{{ $formatValue($req->school_year) }}</dd></div>
                        <div><dt class="text-slate-500">School level</dt><dd class="font-bold">{{ $formatValue($req->school_level) }}</dd></div>
                        <div><dt class="text-slate-500">Submitted</dt><dd class="font-bold">{{ $req->created_at->format('M d, Y h:i A') }}</dd></div>
                        <div><dt class="text-slate-500">Last updated</dt><dd class="font-bold">{{ $req->updated_at->format('M d, Y h:i A') }}</dd></div>
                        <div><dt class="text-slate-500">Current status</dt><dd class="mt-1"><x-request-status-badge :status="$req->status" /></dd></div>
                        <div><dt class="text-slate-500">Remarks</dt><dd class="whitespace-pre-line font-bold">{{ $formatValue($req->remarks) }}</dd></div>
                    </dl>
                    <div class="mt-4">@include('requests.partials.dynamic-answers')</div>
                </section>

                <section class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h4 class="flex items-center gap-2 text-sm font-black text-[#000638]"><span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-100 text-emerald-700">03</span> Purpose and destination</h4>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3"><dt class="text-xs font-bold uppercase text-amber-700">Purpose</dt><dd class="mt-1 text-base font-black text-amber-950">{{ $purposeLabel }}</dd></div>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3"><dt class="text-xs font-bold uppercase text-amber-700">Reason</dt><dd class="mt-1 whitespace-pre-line font-black text-amber-950">{{ $formatValue($req->purpose_details) }}</dd></div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3"><dt class="text-xs font-bold uppercase text-emerald-700">Receiving school</dt><dd class="mt-1 text-base font-black text-emerald-950">{{ $formatValue($req->receiving_school_name) }}</dd></div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 sm:col-span-2"><dt class="text-xs font-bold uppercase text-emerald-700">Complete school address</dt><dd class="mt-1 whitespace-pre-line text-base font-black text-emerald-950">{{ $formatValue($req->receiving_school_address) }}</dd></div>
                        <div><dt class="text-slate-500">Address type</dt><dd class="font-bold">{{ $req->receiving_address_scope === 'overseas' ? 'Overseas' : ($req->receiving_address_scope === 'domestic' ? 'Philippines' : 'Legacy record') }}</dd></div>
                        @if($req->request_purpose === 'employment')
                            <div><dt class="text-slate-500">Employer</dt><dd class="font-bold">{{ $formatValue($req->employer_name) }}</dd></div>
                            <div><dt class="text-slate-500">Employment exception</dt><dd class="font-bold">{{ $req->is_f137_employment_exception ? 'Yes' : 'No' }}</dd></div>
                        @endif
                        <div><dt class="text-slate-500">Review status</dt><dd class="font-bold">{{ $formatValue($req->review_status) }}</dd></div>
                        <div><dt class="text-slate-500">Reviewed at</dt><dd class="font-bold">{{ $req->reviewed_at?->format('M d, Y h:i A') ?? 'Not yet reviewed' }}</dd></div>
                        <div><dt class="text-slate-500">Review notes</dt><dd class="whitespace-pre-line font-bold">{{ $formatValue($req->review_notes) }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($req->request_proof_path)<a href="{{ route('requests.request-proof', $req) }}" target="_blank" rel="noopener" class="max-w-full truncate rounded-lg bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">View receiving-school proof · {{ $req->request_proof_original_name }}</a>@endif
                        @if($req->employment_proof_path)<a href="{{ route('requests.employment-proof', $req) }}" target="_blank" rel="noopener" class="max-w-full truncate rounded-lg bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">View employment proof · {{ $req->employment_proof_original_name }}</a>@endif
                    </div>
                </section>

                @endif
                @if(auth()->user()->role !== 'registrar')

                <section class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
                    <h4 class="flex items-center gap-2 text-sm font-black text-[#000638]"><span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-100 text-amber-700">04</span> Release and payment</h4>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Delivery method</dt><dd class="font-bold">{{ $formatValue($req->delivery_method) }}</dd></div>
                        <div><dt class="text-slate-500">Release location</dt><dd class="font-bold">{{ $formatValue($req->release_location) }}</dd></div>
                        <div><dt class="text-slate-500">Pickup location</dt><dd class="font-bold">{{ $formatValue($req->pickup_location) }}</dd></div>
                        <div><dt class="text-slate-500">Ready for pickup at</dt><dd class="font-bold">{{ $req->ready_for_pickup_at?->format('M d, Y h:i A') ?? 'Not ready' }}</dd></div>
                        <div class="rounded-lg bg-amber-50 p-3"><dt class="text-xs font-bold uppercase text-amber-700">Document fee</dt><dd class="mt-1 text-lg font-black text-amber-950">{{ $req->document_price !== null ? '₱'.number_format((float) $req->document_price, 2) : 'Not set' }}</dd></div>
                        @if($req->document_type === 'Form 137' && $req->request_purpose === 'transfer_enrollment')
                            <div class="rounded-lg bg-cyan-50 p-3"><dt class="text-xs font-bold uppercase text-cyan-700">Delivery method</dt><dd class="mt-1 font-black text-cyan-950">{{ $req->f137_delivery_method === 'lbc' ? 'LBC Shipping' : ($req->f137_delivery_method === 'staff_delivery' ? 'School Staff Delivery' : 'Not assessed') }}</dd></div>
                            <div class="rounded-lg bg-cyan-50 p-3"><dt class="text-xs font-bold uppercase text-cyan-700">Estimated delivery fee</dt><dd class="mt-1 text-lg font-black text-cyan-950">{{ $req->delivery_fee !== null ? '₱'.number_format((float) $req->delivery_fee, 2) : 'Not quoted yet' }}</dd></div>
                            <div class="rounded-lg bg-emerald-50 p-3"><dt class="text-xs font-bold uppercase text-emerald-700">Amount due to Records Officer</dt><dd class="mt-1 text-lg font-black text-emerald-950">{{ $req->delivery_fee !== null ? '₱'.number_format((float) $req->document_price + (float) $req->delivery_fee, 2) : 'Waiting for quotation' }}</dd></div>
                        @endif
                        <div><dt class="text-slate-500">Payment method</dt><dd class="font-bold">{{ $formatValue($req->payment_method) }}</dd></div>
                        <div><dt class="text-slate-500">Payment status</dt><dd class="font-black {{ $req->payment_confirmed ? 'text-emerald-700' : 'text-amber-700' }}">{{ $req->payment_confirmed ? 'Paid — ready to ship' : ($req->delivery_assessed_at ? 'Waiting for payment' : 'Amount not sent yet') }}</dd></div>
                        <div><dt class="text-slate-500">Payment confirmed at</dt><dd class="font-bold">{{ $req->payment_confirmed_at?->format('M d, Y h:i A') ?? 'Not confirmed' }}</dd></div>
                        <div><dt class="text-slate-500">Official receipt number</dt><dd class="font-bold">{{ $formatValue($req->official_receipt_number) }}</dd></div>
                    </dl>
                    @if(!$req->payment_confirmed && $req->delivery_assessed_at && in_array(auth()->user()->role, ['records_officer', 'registrar', 'admin']))
                        <form action="{{ route('requests.confirm-payment', $req->id) }}" method="POST" class="mt-4 grid gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 sm:grid-cols-2">@csrf
                            <div class="sm:col-span-2"><p class="text-sm font-black text-emerald-950">Collect and record the student payment</p><p class="mt-1 text-xs text-emerald-800">As the Records Officer, confirm the exact cash received and enter the official receipt number.</p></div>
                            <label class="text-xs font-bold text-slate-700">Exact amount paid<input type="number" name="payment_amount" step="0.01" min="0" required value="{{ number_format((float) $req->document_price + (float) $req->delivery_fee, 2, '.', '') }}" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm"></label>
                            <label class="text-xs font-bold text-slate-700">Official receipt number<input name="official_receipt_number" maxlength="100" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm"></label>
                            <button class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-black text-white sm:col-span-2">Confirm Cash Payment Received</button>
                        </form>
                    @endif
                    @if($req->payment_proof_path)<a href="{{ route('requests.receipt.uploaded', $req) }}" target="_blank" rel="noopener" class="mt-4 inline-flex rounded-lg bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">View School ID proof ({{ $req->payment_proof_original_name }})</a>@endif
                </section>

                <section class="rounded-2xl border border-teal-200 bg-white p-5 shadow-sm">
                    <h4 class="flex items-center gap-2 text-sm font-black text-[#000638]"><span class="grid h-8 w-8 place-items-center rounded-lg bg-teal-100 text-teal-700">05</span> Transmission and claim</h4>
                    @forelse($req->transmissions as $transmission)
                        <dl class="mt-3 grid gap-2 border-b border-slate-100 pb-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-slate-500">Method</dt><dd class="font-bold">{{ ucfirst(str_replace('_', ' ', $transmission->method)) }}</dd></div>
                            <div><dt class="text-slate-500">Recipient</dt><dd class="font-bold">{{ $transmission->recipient_name }}</dd></div>
                            <div><dt class="text-slate-500">Destination</dt><dd class="font-bold">{{ $transmission->destination }}</dd></div>
                            <div><dt class="text-slate-500">Reference number</dt><dd class="font-bold">{{ $transmission->reference_number }}</dd></div>
                            <div><dt class="text-slate-500">Transmitted at</dt><dd class="font-bold">{{ $transmission->transmitted_at->format('M d, Y h:i A') }}</dd></div>
                            <div><dt class="text-slate-500">Processed by</dt><dd class="font-bold">{{ $transmission->transmitter?->name ?? 'Staff account unavailable' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-slate-500">Proof / remarks</dt><dd class="font-bold">{{ $transmission->proof_original_name }} · {{ $formatValue($transmission->remarks) }}</dd></div>
                        </dl>
                    @empty
                        <p class="mt-3 text-sm text-slate-500">No transmission recorded.</p>
                    @endforelse
                    @if($req->claim)
                        <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                            <div><dt class="text-slate-500">Claimant</dt><dd class="font-bold">{{ $req->claim->claimant_name }}</dd></div>
                            <div><dt class="text-slate-500">Claimant type</dt><dd class="font-bold">{{ ucfirst($req->claim->claimant_type) }}</dd></div>
                            <div><dt class="text-slate-500">Valid ID</dt><dd class="font-bold">{{ $req->claim->valid_id_type }} ending in {{ $req->claim->valid_id_last_four }}</dd></div>
                            <div><dt class="text-slate-500">Claimed at</dt><dd class="font-bold">{{ $req->claim->claimed_at->format('M d, Y h:i A') }}</dd></div>
                            <div><dt class="text-slate-500">Released by</dt><dd class="font-bold">{{ $req->claim->releaser?->name ?? 'Staff account unavailable' }}</dd></div>
                            <div><dt class="text-slate-500">Authorization</dt><dd class="font-bold">{{ $req->claim->authorization_original_name ?? 'Not required' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-slate-500">Claim remarks</dt><dd class="font-bold">{{ $formatValue($req->claim->remarks) }}</dd></div>
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No claim recorded.</p>
                    @endif
                </section>

                <section class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h4 class="flex items-center gap-2 text-sm font-black text-[#000638]"><span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-100 text-indigo-700">06</span> Complete status history</h4>
                    <div class="mt-3">@include('requests.partials.status-timeline', ['histories' => $req->statusHistories, 'staff' => true])</div>
                </section>
                @endif

                @if(isset($transitions))
                <section class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5 shadow-sm lg:col-span-2 {{ auth()->user()->role === 'records_officer' ? 'order-first' : '' }}">
                    @if(auth()->user()->role === 'registrar')
                    <h4 class="text-lg font-black text-[#000638]">Approve or reject this request</h4>
                    <p class="mt-1 text-sm text-slate-600">Open the prepared document, check that it matches the summary above, then choose your decision.</p>
                    @if($req->hasPreparedDocument() && !$req->is_manually_prepared)
                        <a href="{{ route('requests.document-preview', $req) }}" target="_blank" rel="noopener" class="mt-4 inline-flex rounded-lg border border-[#000638] bg-white px-5 py-2.5 text-sm font-black text-[#000638]">View prepared document</a>
                    @elseif($req->is_manually_prepared)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="font-black">Manually prepared document</p>
                            <p class="mt-1">{{ $req->manual_preparation_reason }}</p>
                            <p class="mt-2 text-xs">Prepared by {{ $req->manualPreparer?->name ?? 'Records Officer' }} · {{ $req->manually_prepared_at?->format('M d, Y h:i A') }}</p>
                        </div>
                    @endif
                    @if($allowedStatuses !== [])
                        <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="mt-4 rounded-xl border border-blue-200 bg-white p-4"
                              data-request-identifier="{{ $req->ticket_number ?? '#'.$req->id }}"
                              onsubmit="const status=this.elements.status.value; const remarks=this.elements.remarks; if(status==='rejected' && !remarks.value.trim()){remarks.setCustomValidity('Enter the rejection reason.');remarks.reportValidity();return false;} remarks.setCustomValidity(''); return status!=='rejected' || confirm('Reject ' + this.dataset.requestIdentifier + '?');">
                            @csrf
                            <input type="hidden" name="status" value="">
                            <label for="registrar-review-notes-{{ $req->id }}" class="block text-sm font-bold text-slate-700">Registrar note <span class="font-normal text-slate-500">(required only when rejecting)</span></label>
                            <textarea id="registrar-review-notes-{{ $req->id }}" name="remarks" rows="2" class="mt-2 w-full rounded-lg border-slate-300 bg-white text-sm" placeholder="Write a short reason if you reject the request."></textarea>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach($allowedStatuses as $target)
                                    <button type="button" data-status-target="{{ $target }}" onclick="this.form.elements.status.value=this.dataset.statusTarget; this.form.elements.remarks.required=this.dataset.statusTarget==='rejected'; this.form.requestSubmit();" class="rounded-lg px-5 py-3 text-sm font-black {{ $target === 'rejected' ? 'border border-red-300 bg-red-50 text-red-700' : 'bg-emerald-700 text-white' }}">{{ $target === 'rejected' ? 'Reject request' : 'Approve request' }}</button>
                                @endforeach
                            </div>
                        </form>
                    @else
                        <p class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">This request has already been reviewed or is not ready for Registrar approval.</p>
                    @endif
                    @else
                    <h4 class="text-xs font-black uppercase tracking-wider text-[#000638]">Document preparation</h4>
                    <p class="mt-1 text-sm text-slate-600">Prepare the document and forward it to the Registrar for review. Transmission is handled separately after approval.</p>
                    @if($req->request_type_id)
                        <p class="mt-3 rounded-xl border border-indigo-200 bg-white px-4 py-3 text-sm font-semibold text-indigo-800">Custom request — prepare it manually, then forward it to the Registrar for review.</p>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if(!$req->request_type_id && str_starts_with($req->document_type, 'Certificate of '))
                            <a href="{{ route('certifications.index', ['request_id' => $req->id]) }}" class="rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638]">Open Certification Maker</a>
                        @elseif(!$req->request_type_id && str_contains($documentName, 'good moral'))
                            <a href="{{ route('good-moral.index', ['request_id' => $req->id]) }}" class="rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638]">Open Good Moral Maker</a>
                        @elseif(!$req->request_type_id && in_array($documentName, ['form 137', 'form 138', 'f137', 'f138']))
                            @php($schoolForm = str_contains($documentName, '137') ? 'f137' : 'f138')
                            <a href="{{ route('school-forms.'.$schoolForm.'.preview', array_filter(['student' => $req->student_number, 'school_year' => $schoolForm === 'f138' ? $req->school_year : null, 'request_id' => $req->id])) }}" class="rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638]">Process {{ $req->document_type }}</a>
                        @elseif(!$req->request_type_id && str_contains($documentName, 'diploma'))
                            <a href="{{ route('diploma.index', ['request_id' => $req->id]) }}" class="rounded-lg border border-[#000638] bg-white px-4 py-2 text-sm font-bold text-[#000638]">Process physical diploma</a>
                        @endif
                        @if($req->hasPreparedDocument() && !$req->is_manually_prepared)
                            <a href="{{ route('requests.document-preview', $req) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">Preview document</a>
                            <a href="{{ route('requests.document-download', $req) }}" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white">Download document</a>
                        @endif
                    </div>
                    @if($req->is_manually_prepared)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="font-black">{{ $req->document_type }} prepared through manual procedure</p>
                            <p class="mt-1">{{ $req->manual_preparation_reason }}</p>
                            <p class="mt-2 text-xs">Recorded by {{ $req->manualPreparer?->name ?? 'Records Officer' }} · {{ $req->manually_prepared_at?->format('M d, Y h:i A') }}</p>
                        </div>
                    @endif
                    @if(!str_contains(strtolower($req->document_type), 'diploma') && in_array($req->status, ['pending', 'processing']) && auth()->user()->role === 'records_officer')
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-xl border border-blue-200 bg-white p-4">
                                <p class="text-sm font-black text-[#000638]">Process using system</p>
                                <p class="mt-1 text-xs text-slate-500">Use the available imported student records and the Form 137 generator above.</p>
                            </div>
                            <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="rounded-xl border border-amber-300 bg-amber-50 p-4" onsubmit="return confirm('Use the manual F137 procedure and forward this request to the Registrar for review?');">
                                @csrf
                                <input type="hidden" name="status" value="processed">
                                <input type="hidden" name="manual_preparation" value="1">
                                <p class="text-sm font-black text-amber-900">Manual {{ $req->document_type }} procedure</p>
                                <p class="mt-1 text-xs text-amber-800">Use only when required data cannot be retrieved by the system.</p>
                                <label for="manual-reason-{{ $req->id }}" class="mt-3 block text-xs font-bold text-slate-700">Reason manual preparation is needed</label>
                                <textarea id="manual-reason-{{ $req->id }}" name="manual_preparation_reason" required rows="3" class="mt-1 w-full rounded-lg border-amber-300 bg-white text-sm" placeholder="Example: Historical grades are missing from the imported records."></textarea>
                                <label for="manual-remarks-{{ $req->id }}" class="mt-3 block text-xs font-bold text-slate-700">Preparation notes</label>
                                <textarea id="manual-remarks-{{ $req->id }}" name="remarks" required rows="2" class="mt-1 w-full rounded-lg border-amber-300 bg-white text-sm" placeholder="State how the information was verified and prepared."></textarea>
                                <button type="submit" class="mt-3 w-full rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-black text-white hover:bg-amber-700">Use Manual Procedure & Forward to Registrar</button>
                            </form>
                        </div>
                    @endif
                    @if($allowedStatuses !== [] && $req->status !== 'ready_for_transmission' && $req->status !== 'transmitted')
                        <form action="{{ route('requests.update-status', $req->id) }}" method="POST" enctype="multipart/form-data" class="mt-4"
                              data-request-identifier="{{ $req->ticket_number ?? '#'.$req->id }}"
                              onsubmit="const status=this.elements.status.value; const id=this.dataset.requestIdentifier; const remarks=this.elements.remarks; if(status==='rejected' && !remarks.value.trim()){remarks.setCustomValidity('Enter the rejection reason.');remarks.reportValidity();return false;} remarks.setCustomValidity(''); if(status==='rejected') return confirm('Reject request ' + id + '? This cannot be undone through Request Management.'); if(status==='completed') return confirm('Mark request ' + id + ' as released? This cannot be undone through Request Management.'); if(status==='ready_to_release') return confirm('Mark request ' + id + ' as ready for release? Confirm that the issued document is ready before continuing.'); return true;">
                            @csrf
                            <input type="hidden" name="status" value="">
                            <label for="modal-status-remarks-{{ $req->id }}" class="block text-xs font-bold text-slate-700">Remarks</label>
                            <input id="modal-status-remarks-{{ $req->id }}" name="remarks" value="{{ $req->remarks }}" class="mt-1 w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Add an update or rejection reason">
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($allowedStatuses as $target)
                                    <button type="button" data-status-target="{{ $target }}" onclick="this.form.elements.status.value=this.dataset.statusTarget; this.form.elements.remarks.required=this.dataset.statusTarget==='rejected'; this.form.requestSubmit();" class="rounded-lg px-5 py-3 text-base font-bold {{ $target === 'rejected' ? 'border border-red-200 bg-white text-red-700' : (in_array($target, ['claimed', 'completed'], true) ? 'bg-emerald-700 text-white' : 'bg-[#000638] text-white') }}">{{ match($target) { 'processing' => 'Start / Return to Processing', 'processed' => 'Send to Registrar Review', 'claimed' => 'Mark as Claimed', 'completed' => str_contains(strtolower($req->document_type), 'form 137') ? 'Complete Request' : 'Mark as Claimed', 'rejected' => 'Reject Request', default => ucfirst(str_replace('_', ' ', $target)) } }}</button>
                                @endforeach
                            </div>
                        </form>
                    @elseif($req->status !== 'ready_for_transmission' && $req->status !== 'transmitted')
                        <p class="mt-4 text-sm text-slate-500">No Records Officer action is available at this stage.</p>
                    @endif
                    @endif
                </section>

                @if($req->document_type === 'Form 137' && $req->request_purpose === 'transfer_enrollment')
                <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm lg:col-span-2">
                    <h4 class="text-xs font-black uppercase tracking-wider text-emerald-900">School-to-school transmission</h4>
                    <p class="mt-1 text-sm text-emerald-800">Follow the steps in order. Shipping stays locked until the Records Officer records the payment.</p>
                    <ol class="mt-4 grid gap-2 text-sm sm:grid-cols-4">
                        <li class="rounded-xl border {{ in_array($req->status, ['ready_for_transmission', 'transmitted', 'completed']) ? 'border-emerald-300 bg-white' : 'border-slate-200 bg-slate-50' }} p-3"><span class="font-black">1. Registrar approval</span><p class="mt-1 text-xs text-slate-600">{{ in_array($req->status, ['ready_for_transmission', 'transmitted', 'completed']) ? 'Completed' : 'Waiting' }}</p></li>
                        <li class="rounded-xl border {{ $req->delivery_assessed_at ? 'border-emerald-300 bg-white' : 'border-amber-300 bg-amber-50' }} p-3"><span class="font-black">2. Send amount</span><p class="mt-1 text-xs text-slate-600">{{ $req->delivery_assessed_at ? 'Quotation sent' : 'Records Officer action' }}</p></li>
                        <li class="rounded-xl border {{ $req->payment_confirmed ? 'border-emerald-300 bg-white' : 'border-amber-300 bg-amber-50' }} p-3"><span class="font-black">3. Records payment</span><p class="mt-1 text-xs text-slate-600">{{ $req->payment_confirmed ? 'Paid and confirmed' : 'Waiting for student payment' }}</p></li>
                        <li class="rounded-xl border {{ in_array($req->status, ['transmitted', 'completed']) ? 'border-emerald-300 bg-white' : 'border-slate-200 bg-slate-50' }} p-3"><span class="font-black">4. Ship / deliver</span><p class="mt-1 text-xs text-slate-600">{{ in_array($req->status, ['transmitted', 'completed']) ? 'Transmission recorded' : ($req->payment_confirmed ? 'Ready now' : 'Locked until paid') }}</p></li>
                    </ol>
                    @if($req->status === 'ready_for_transmission' && auth()->user()->role === 'records_officer')
                        @if(!$req->delivery_assessed_at)
                        <form action="{{ route('requests.assess-f137-delivery', $req->id) }}" method="POST" class="mt-4 rounded-xl border border-cyan-200 bg-white p-4">@csrf
                            <p class="text-sm font-black text-cyan-950">Prepare the amount for the student</p>
                            <p class="mt-1 text-xs text-slate-600">Get an LBC quotation using the receiving-school address. Enter that estimated fee before anything is shipped.</p>
                            <div class="mt-3 grid gap-2 rounded-lg bg-slate-50 p-3 text-sm sm:grid-cols-3">
                                <div><span class="text-xs text-slate-500">Document fee</span><p class="font-black">₱{{ number_format((float) $req->document_price, 2) }}</p></div>
                                <div><span class="text-xs text-slate-500">Estimated shipping</span><p class="font-black" data-delivery-preview>Enter below</p></div>
                                <div><span class="text-xs text-slate-500">Total amount due</span><p class="font-black text-emerald-700" data-total-preview>Calculated after entry</p></div>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <label class="text-xs font-bold text-slate-700">Delivery method<select name="f137_delivery_method" required class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Select method</option><option value="staff_delivery">School Staff Delivery</option><option value="lbc">LBC Shipping</option></select></label>
                                <label class="text-xs font-bold text-slate-700">Estimated delivery fee<input type="number" name="delivery_fee" required min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="0.00" oninput="const fee=Number(this.value||0), form=this.form; form.querySelector('[data-delivery-preview]').textContent='₱'+fee.toFixed(2); form.querySelector('[data-total-preview]').textContent='₱'+({{ (float) $req->document_price }}+fee).toFixed(2);"></label>
                            </div>
                            <button class="mt-3 rounded-lg bg-cyan-700 px-5 py-2.5 text-sm font-black text-white">Send Amount to Student</button>
                        </form>
                        @elseif(!$req->payment_confirmed)
                            <div class="mt-4 rounded-xl border border-amber-200 bg-white p-4 text-sm text-amber-900"><p class="font-black">Waiting for student payment</p><p class="mt-1">Collect <strong>₱{{ number_format((float) $req->document_price + (float) $req->delivery_fee, 2) }}</strong> in cash as the Records Officer. Then record the amount and official receipt in the Payment section above.</p></div>
                        @else
                        <form action="{{ route('requests.update-status', $req->id) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                            @csrf
                            <div class="grid gap-3 rounded-xl border border-emerald-200 bg-white p-4 sm:grid-cols-2">
                                <div class="text-xs font-bold text-slate-700">Transmission method<input type="hidden" name="transmission_method" value="{{ $req->f137_delivery_method }}"><div class="mt-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm font-black text-slate-800">{{ $req->f137_delivery_method === 'lbc' ? 'LBC Express' : 'School Staff Delivery' }}</div></div>
                                <label class="text-xs font-bold text-slate-700">Recipient name<input name="recipient_name" required class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="text-xs font-bold text-slate-700 sm:col-span-2">Destination<textarea name="transmission_destination" required rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm">{{ $req->receiving_school_name }} — {{ $req->receiving_school_address }}</textarea></label>
                                <label class="text-xs font-bold text-slate-700">{{ $req->f137_delivery_method === 'lbc' ? 'LBC tracking number' : 'Delivery reference (optional)' }}<input name="reference_number" {{ $req->f137_delivery_method === 'lbc' ? 'required' : '' }} class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="text-xs font-bold text-slate-700">Transmission proof {{ $req->f137_delivery_method === 'lbc' ? '' : '(optional)' }}<input type="file" name="transmission_proof" {{ $req->f137_delivery_method === 'lbc' ? 'required' : '' }} accept=".jpg,.jpeg,.png,.pdf" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm"></label>
                                <label class="text-xs font-bold text-slate-700 sm:col-span-2">Transmission remarks<input name="remarks" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                            </div>
                            <button type="submit" name="status" value="transmitted" class="mt-3 rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-black text-white">Confirm Shipped / Delivered</button>
                        </form>
                        @endif
                    @elseif($req->status === 'transmitted' && auth()->user()->role === 'records_officer')
                        <form action="{{ route('requests.update-status', $req->id) }}" method="POST" class="mt-4">@csrf<button type="submit" name="status" value="completed" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-black text-white">Complete Request</button></form>
                    @else
                        <p class="mt-4 rounded-xl bg-white px-4 py-3 text-sm text-slate-600">{{ $req->status === 'processed' ? 'Waiting for Registrar approval.' : 'Transmission will unlock after approval.' }}</p>
                    @endif
                </section>
                @endif
                @endif
            </div>
        </div>
    </div>
</dialog>
