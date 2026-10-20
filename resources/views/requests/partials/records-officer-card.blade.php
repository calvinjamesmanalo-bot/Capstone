@php
    $stage = match ($req->status) {
        'pending' => ['New request', 'bg-amber-50 text-amber-900 ring-amber-300', 'border-l-amber-400'],
        'processing' => ['Processing', 'bg-sky-50 text-sky-800 ring-sky-200', 'border-l-sky-400'],
        'processed' => ['Waiting for Registrar', 'bg-violet-50 text-violet-800 ring-violet-200', 'border-l-violet-400'],
        'ready_to_release' => ['Ready to release', 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'border-l-emerald-400'],
        'ready_for_pickup' => ['Ready for pickup', 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'border-l-emerald-400'],
        'ready_for_transmission' => ['Ready to send', 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'border-l-emerald-400'],
        'transmitted' => ['Sent to receiving school', 'bg-teal-50 text-teal-800 ring-teal-200', 'border-l-teal-400'],
        default => [ucfirst(str_replace('_', ' ', $req->status)), 'bg-slate-100 text-slate-700 ring-slate-200', 'border-l-slate-400'],
    };
    $nextStep = match ($req->status) {
        'pending' => 'Open and start preparing the document',
        'processing' => 'Finish the document and send it for review',
        'processed' => 'No action yet — waiting for Registrar review',
        'ready_to_release' => 'Release the document to the student',
        'ready_for_pickup' => 'Hand over the document, then mark as claimed',
        'ready_for_transmission' => ! $req->delivery_assessed_at
            ? 'Set the estimated delivery fee and notify the student'
            : (! $req->payment_confirmed
                ? 'Collect the cash payment and record the official receipt'
                : 'Payment confirmed — ship or deliver the document'),
        'transmitted' => 'Confirm completion after delivery',
        default => 'Open the request to view the next action',
    };
@endphp

<article id="request-{{ $req->id }}" class="scroll-mt-6 rounded-2xl border border-slate-200 border-l-[6px] {{ $stage[2] }} bg-white p-5 shadow-sm transition hover:shadow-md target:ring-2 target:ring-emerald-400" aria-label="Request {{ $req->ticket_number ?? '#'.$req->id }}">
    <div class="flex items-start justify-between gap-4">
        <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-[#000638] text-lg font-black text-white">{{ strtoupper(mb_substr($req->student->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <p class="text-base font-black leading-5 text-[#000638]">{{ $req->student->name }}</p>
                <p class="mt-1 text-sm font-bold text-slate-700">{{ $req->document_type }}</p>
                <p class="mt-1 text-sm text-slate-600">Student No. {{ $req->student_number }}</p>
            </div>
        </div>
        <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $stage[1] }}">{{ $stage[0] }}</span>
    </div>
    <div class="mt-4 rounded-xl bg-slate-50 p-3">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Next step</p>
        <p class="mt-1 text-sm font-bold leading-5 text-slate-800">{{ $nextStep }}</p>
    </div>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
        <span>{{ $req->ticket_number ?? '#'.$req->id }}</span>
        <span>{{ $req->created_at->format('M d, Y h:i A') }}</span>
    </div>
    <button type="button" onclick="document.getElementById('request-details-{{ $req->id }}').showModal()" class="mt-4 min-h-12 w-full rounded-xl bg-[#000638] px-5 py-3 text-base font-black text-white hover:bg-[#10175a]">Open Request</button>
</article>

@include('requests.partials.request-details-modal', ['req' => $req, 'transitions' => $transitions])
