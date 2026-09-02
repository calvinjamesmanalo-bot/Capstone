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
        <div class="flex flex-wrap gap-3">
            <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Print preview</button>
            <a href="{{ route('requests.index') }}{{ $requestId !== '' ? '#request-'.$requestId : '' }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                Update Request Status
            </a>
            <form method="POST" action="{{ route('certifications.pdf') }}" target="_blank">@csrf
                @foreach(['request_id','certificate_type','student_number','student_name','grade_level','section','school_year','issue_date','expires_at','purpose','recognition'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $form[$field] ?? '' }}">@endforeach
                <button name="output" value="stream" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">View draft PDF</button>
            </form>
            @if($requestId !== '' && in_array(auth()->user()->role, ['admin', 'records_officer']))
                <button type="button" onclick="document.getElementById('finalize-modal').classList.remove('hidden')" class="rounded-lg bg-[#062b63] px-4 py-2 text-sm font-semibold text-white">Finalize &amp; Issue</button>
            @endif
        </div>
    </div>
    <div class="overflow-auto bg-slate-200 py-6"><div class="min-w-[210mm]">@include('certifications.partials.certificate')</div></div>
</div>

@if($requestId !== '' && in_array(auth()->user()->role, ['admin', 'records_officer']))
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
