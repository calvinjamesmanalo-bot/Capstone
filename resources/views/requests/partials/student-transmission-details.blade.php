@php($latestTransmission = $req->transmissions->sortByDesc('transmitted_at')->first())

@if($req->document_type === 'Form 137' && $req->request_purpose === 'transfer_enrollment' && in_array($req->status, ['ready_for_transmission', 'transmitted', 'completed']))
    <section class="mt-4 w-full rounded-2xl border border-cyan-200 bg-cyan-50/70 p-4 text-left" aria-label="Form 137 transmission progress">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs font-black uppercase tracking-wider text-cyan-900">School-to-school transmission</p>
                <p class="mt-1 text-xs text-cyan-800">Your Form 137 is handled directly by the school. The document is not released to the student.</p>
            </div>
            <span class="rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase text-cyan-800 ring-1 ring-cyan-200">
                {{ $req->status === 'ready_for_transmission' ? 'Approved · Preparing to send' : ($req->status === 'transmitted' ? 'Sent to receiving school' : 'Transmission completed') }}
            </span>
        </div>

        <ol class="mt-4 grid gap-2 text-xs sm:grid-cols-3">
            <li class="rounded-xl bg-white p-3 ring-1 ring-cyan-100"><span class="font-black text-cyan-700">1. Registrar approved</span><p class="mt-1 text-slate-500">The generated F137 passed Registrar review.</p></li>
            <li class="rounded-xl bg-white p-3 ring-1 ring-cyan-100"><span class="font-black text-cyan-700">2. Records Officer sends</span><p class="mt-1 text-slate-500">The Records Officer sends it directly to the receiving school.</p></li>
            <li class="rounded-xl bg-white p-3 ring-1 ring-cyan-100"><span class="font-black text-cyan-700">3. Request completed</span><p class="mt-1 text-slate-500">The request closes after the transmission is recorded.</p></li>
        </ol>

        @if($req->delivery_assessed_at)
            <div class="mt-4 grid gap-3 rounded-xl border border-cyan-200 bg-white p-4 text-xs sm:grid-cols-2 lg:grid-cols-4">
                <div><span class="text-slate-500">Delivery method</span><p class="mt-1 font-black text-slate-900">{{ $req->f137_delivery_method === 'lbc' ? 'LBC Shipping' : 'School Staff Delivery' }}</p></div>
                <div><span class="text-slate-500">Document fee</span><p class="mt-1 font-black text-slate-900">₱{{ number_format((float) $req->document_price, 2) }}</p></div>
                <div><span class="text-slate-500">Estimated delivery fee</span><p class="mt-1 font-black text-slate-900">₱{{ number_format((float) $req->delivery_fee, 2) }}</p></div>
                <div><span class="text-slate-500">Amount due to Records Officer</span><p class="mt-1 text-base font-black text-emerald-700">₱{{ number_format((float) $req->document_price + (float) $req->delivery_fee, 2) }}</p></div>
                <p class="rounded-lg bg-amber-50 px-3 py-2 font-bold text-amber-800 sm:col-span-2 lg:col-span-4">{{ $req->payment_confirmed ? 'Payment confirmed. Your Form 137 is ready for shipping or delivery.' : 'Please pay the amount due in cash to the school Records Officer and keep your official receipt.' }}</p>
            </div>
        @else
            <p class="mt-4 rounded-xl bg-white px-4 py-3 text-xs font-semibold text-slate-600">The Records Officer is reviewing the receiving-school address and will notify you of the delivery method and total cash payment.</p>
        @endif

        @if($latestTransmission)
            <dl class="mt-4 grid gap-3 border-t border-cyan-200 pt-4 text-xs sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-slate-500">Sent to</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $latestTransmission->recipient_name }}</dd></div>
                <div><dt class="text-slate-500">Destination</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $latestTransmission->destination }}</dd></div>
                <div><dt class="text-slate-500">Transmission method</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $latestTransmission->method === 'lbc' ? 'LBC Express' : ucfirst(str_replace('_', ' ', $latestTransmission->method)) }}</dd></div>
                <div><dt class="text-slate-500">Reference number</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $latestTransmission->reference_number }}</dd></div>
                <div><dt class="text-slate-500">Date and time sent</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $latestTransmission->transmitted_at->format('M d, Y · h:i A') }}</dd></div>
                <div><dt class="text-slate-500">Next step</dt><dd class="mt-0.5 font-bold text-slate-800">{{ $req->status === 'completed' ? 'No further action is required.' : 'Wait for the school to complete the request record.' }}</dd></div>
            </dl>
            @if($latestTransmission->method === 'lbc')
                <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-cyan-200 pt-4">
                    <a href="https://www.lbcexpress.com/ph/track" target="_blank" rel="noopener noreferrer" onclick="navigator.clipboard?.writeText(@js($latestTransmission->reference_number))" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-red-700">Copy Reference &amp; Track on LBC</a>
                    <p class="text-xs text-slate-600">The reference number will be copied. Paste it into the official LBC Track &amp; Trace page.</p>
                </div>
            @endif
        @else
            <p class="mt-4 rounded-xl bg-white px-4 py-3 text-xs font-semibold text-slate-600">The document is approved. Transmission details will appear here after the Records Officer sends it.</p>
        @endif
    </section>
@endif
