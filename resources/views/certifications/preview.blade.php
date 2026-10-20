@extends('layouts.app')

@section('title', 'Certificate Preview')
@section('page_title', 'Certificate Preview')
@section('page_subtitle', $certificate['label'])

@section('content')
@php($requestId = $form['request_id'] ?? '')
<div class="space-y-6">
    @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 font-semibold text-red-800">{{ session('error') }}</div>@endif
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-slate-200 rounded-xl p-4 no-print">
        <a href="{{ route('certifications.index', $requestId !== '' ? ['request_id' => $requestId] : []) }}" class="text-sm font-semibold text-[#062b63]">&larr; Edit details</a>
        @if($requestId !== '')<a href="{{ route('requests.index', ['open_request' => $requestId]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Back to request details</a>@endif
    </div>
    @if($requestId !== '' && in_array(auth()->user()->role, ['records_officer', 'registrar']))
        <section class="rounded-xl border border-slate-300 bg-white p-5 shadow-sm no-print">
            @if($errors->any())<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <h2 class="text-lg font-bold text-slate-900">Final document check</h2>
            <p class="mt-1 text-sm text-slate-600">Confirm the certificate details below. Checking the confirmation enables all document actions.</p>
            <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 text-sm font-semibold text-slate-800">
                <input id="certificate-correct" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-[#000638]">
                <span>I reviewed the student information, certificate type, school details, purpose, and dates. Everything is correct.</span>
            </label>
            <div class="mt-4 flex flex-wrap gap-3">
                <button id="certificate-print" type="button" onclick="window.print()" disabled class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Print preview</button>
                <form method="POST" action="{{ route('certifications.pdf') }}" target="_blank">@csrf
                    @foreach(['request_id','certificate_type','student_number','student_name','grade_level','section','school_year','issue_date','expires_at','purpose','recognition'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $form[$field] ?? '' }}">@endforeach
                    <button id="certificate-pdf" name="output" value="stream" disabled class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">View protected PDF</button>
                </form>
                @if(auth()->user()->role === 'records_officer')
                    <form method="POST" action="{{ route('certifications.forward') }}" onsubmit="const button=this.querySelector('button'); button.disabled=true; button.textContent='Forwarding…';">@csrf
                        @foreach(['request_id','certificate_type','student_number','student_name','grade_level','section','school_year','issue_date','expires_at','purpose','recognition'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $form[$field] ?? '' }}">@endforeach
                        <button id="certificate-forward" disabled class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">Forward to Registrar</button>
                    </form>
                @endif
            </div>
        </section>
        <script>
            (() => {
                const checkbox = document.getElementById('certificate-correct');
                const buttons = ['certificate-print', 'certificate-pdf', 'certificate-forward'].map((id) => document.getElementById(id)).filter(Boolean);
                checkbox.addEventListener('change', () => buttons.forEach((button) => { button.disabled = !checkbox.checked; }));
            })();
        </script>
    @endif
    <div class="overflow-auto bg-slate-200 py-6"><div class="min-w-[210mm]">@include('certifications.partials.certificate')</div></div>
</div>

@if($requestId !== '' && auth()->user()->role === 'admin')
<div id="finalize-modal" class="hidden fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-black text-slate-900">Finalize Official Document?</h2>
        <p class="mt-3 text-sm leading-6 text-slate-600">This assigns a permanent document ID, adds the QR, embeds the X.509 PDF signature, hashes the final signed PDF, and stores the immutable official copy. Corrections afterward require revoke and reissue.</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" onclick="document.getElementById('finalize-modal').classList.add('hidden')" class="rounded-xl border border-slate-300 px-4 py-2 font-bold text-slate-700">Cancel</button>
            <form method="POST" action="{{ route('certifications.finalize') }}" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent='Finalizing official document…';">@csrf
                @foreach(['request_id','certificate_type','student_number','student_name','grade_level','section','school_year','issue_date','expires_at','purpose','recognition'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $form[$field] ?? '' }}">@endforeach
                <button class="rounded-xl bg-emerald-600 px-5 py-2 font-black text-white disabled:opacity-60">Confirm &amp; Issue</button>
            </form>
        </div>
    </div>
</div>
@endif
<style>@media print { .no-print, aside, header, footer { display: none !important; } main { margin: 0 !important; } .page-content { padding: 0 !important; } }</style>
@include('certifications.partials.styles')
@endsection
