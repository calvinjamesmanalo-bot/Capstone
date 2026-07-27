@extends('layouts.app')

@section('title', 'Certificate Preview')
@section('page_title', 'Certificate Preview')
@section('page_subtitle', $certificate['label'])

@section('content')
@php($requestId = $form['request_id'] ?? '')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-slate-200 rounded-xl p-4 no-print">
        <a href="{{ route('certifications.index', $requestId !== '' ? ['request_id' => $requestId] : []) }}" class="text-sm font-semibold text-[#062b63]">&larr; Edit details</a>
        <div class="flex flex-wrap gap-3">
            <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Print preview</button>
            <a href="{{ route('requests.index') }}{{ $requestId !== '' ? '#request-'.$requestId : '' }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                Update Request Status
            </a>
            <form method="POST" action="{{ route('certifications.pdf') }}">@csrf
                @foreach(['request_id','certificate_type','student_number','student_name','grade_level','section','school_year','issue_date','purpose','recognition'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $form[$field] ?? '' }}">@endforeach
                <button name="output" value="download" class="rounded-lg bg-[#062b63] px-4 py-2 text-sm font-semibold text-white">Download PDF</button>
            </form>
        </div>
    </div>
    <div class="overflow-auto bg-slate-200 py-6"><div class="min-w-[210mm]">@include('certifications.partials.certificate')</div></div>
</div>
<style>@media print { .no-print, aside, header, footer { display: none !important; } main { margin: 0 !important; } .page-content { padding: 0 !important; } }</style>
@include('certifications.partials.styles')
@endsection
