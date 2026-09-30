@extends('layouts.app')

@section('title', 'Diploma Processing')
@section('page_title', 'Diploma Management')
@section('page_subtitle', 'Process the official physical diploma')

@php
    $isProcessed = $docRequest && in_array($docRequest->status, ['processed', 'ready_to_release', 'completed'], true);
    $requirements = [
        'Completed all academic units',
        'Financial clearance (no balance)',
        'Library clearance (returned books)',
        'Final grades submitted and verified',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white shadow-sm">
        <div class="flex items-center gap-6 border-b border-slate-100 bg-slate-50/70 p-8 sm:p-10">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-amber-600 shadow-sm">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" /></svg>
            </div>
            <div>
                <h2 class="text-2xl font-black uppercase tracking-tight text-slate-800">Physical Diploma</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Diplomas are prepared and released as official physical copies only.</p>
            </div>
        </div>

        @if(!$docRequest)
            <div class="p-8 sm:p-10">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-900">Open a diploma request from Request Management to process its physical copy.</div>
                <a href="{{ route('requests.index') }}" class="mt-6 inline-flex rounded-xl bg-[#000638] px-6 py-3 text-sm font-bold text-white hover:bg-[#10175a]">Return to requests</a>
            </div>
        @else
            <form action="{{ route('diploma.submit') }}" method="POST" class="space-y-8 p-8 sm:p-10">
                @csrf
                <input type="hidden" name="request_id" value="{{ $docRequest->id }}">
                <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Student</p><p class="mt-1 font-bold text-slate-800">{{ $docRequest->student?->name ?? $docRequest->student_number }}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Request</p><p class="mt-1 font-bold text-slate-800">{{ $docRequest->ticket_number ?? '#'.$docRequest->id }}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Release method</p><p class="mt-1 font-bold capitalize text-slate-800">{{ $docRequest->delivery_method ?? 'pickup' }}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Status</p><p class="mt-1 font-bold capitalize text-slate-800">{{ str_replace('_', ' ', $docRequest->status) }}</p></div>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Clearance checklist</h3>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach($requirements as $index => $requirement)
                            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-transparent bg-slate-50 p-5 transition hover:border-amber-100">
                                <input type="checkbox" name="checklist[]" value="{{ $index }}" required @checked($isProcessed) class="h-6 w-6 rounded-lg border-2 border-slate-200 text-amber-600 focus:ring-amber-500">
                                <span class="text-sm font-bold text-slate-600">{{ $requirement }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('checklist')<p class="mt-3 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-4 border-t border-slate-100 pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs font-semibold text-slate-500">No PDF, preview, or digital diploma will be generated.</p>
                    <button type="submit" @disabled($isProcessed) class="rounded-2xl bg-emerald-600 px-8 py-4 text-xs font-black uppercase tracking-wide text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">{{ $isProcessed ? 'Already forwarded' : 'Forward physical request' }}</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
