<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>F138 Preview - {{ $student->name }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) @vite(['resources/css/app.css', 'resources/js/app.js']) @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
<header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Draft · Not officially issued</p>
            <h1 class="text-xl font-bold">F138 Draft Preview</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('school-forms.home', ['student' => $student->student_number ?: $student->lrn, 'school_year' => $enrollment->school_year]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">Back</a>
            <a href="{{ route('school-forms.f138.pdf', ['student' => $student->student_number ?: $student->lrn, 'school_year' => $enrollment->school_year, 'request_id' => $requestId]) }}" target="_blank" class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-bold text-slate-700">View draft PDF</a>
            @if($issuedDocument?->status === 'valid' && $issuedDocument?->officialArtifact)
                <a href="{{ route('documents.issued', $issuedDocument) }}" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white">View issued document</a>
            @elseif($requestId && in_array(auth()->user()->role, ['admin', 'records_officer']))
                <button type="button" onclick="document.getElementById('finalize-modal').classList.remove('hidden')" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white">Finalize &amp; Issue</button>
            @endif
        </div>
    </div>
</header>

<main class="relative mx-auto max-w-6xl px-5 py-8">
    @if(session('error'))<div class="relative z-20 mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 font-semibold text-red-800">{{ session('error') }}</div>@endif
    <div class="pointer-events-none absolute inset-x-0 top-72 z-10 -rotate-12 text-center text-7xl font-black tracking-[.18em] text-red-700/10">DRAFT</div>
    <section class="mb-6 rounded-xl border border-slate-300 bg-white p-6 shadow-sm">
        <div class="grid gap-5 sm:grid-cols-3">
            <div><p class="text-xs font-bold uppercase text-slate-500">Learner name</p><p class="mt-1 text-lg font-bold">{{ $student->name }}</p></div>
            <div><p class="text-xs font-bold uppercase text-slate-500">Student number</p><p class="mt-1 font-semibold">{{ $student->student_number ?: '—' }}</p></div>
            <div><p class="text-xs font-bold uppercase text-slate-500">LRN</p><p class="mt-1 font-semibold">{{ $student->lrn ?: '—' }}</p></div>
        </div>
    </section>

    <section class="mb-7 overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-300 bg-slate-50 px-5 py-4">
            <div>
                <p class="text-xs font-bold uppercase text-slate-500">Classified as</p>
                <h2 class="text-lg font-bold">{{ $enrollment->level }} &middot; {{ $enrollment->section }}</h2>
                <p class="mt-1 text-sm text-slate-600">Adviser/Teacher: <strong>{{ $enrollment->adviser_name ?: 'Not found in uploaded sheet' }}</strong></p>
            </div>
            <div class="text-right"><p class="text-xs font-bold uppercase text-slate-500">School year</p><p class="font-bold">{{ $enrollment->school_year }}</p></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border-b border-r border-slate-300 px-4 py-3 text-left">Learning area</th>
                        @foreach ([1, 2, 3, 4] as $quarter)<th class="border-b border-r border-slate-300 px-3 py-3 text-center">Q{{ $quarter }}</th>@endforeach
                        <th class="border-b border-r border-slate-300 px-3 py-3 text-center">Final rating</th>
                        <th class="border-b border-slate-300 px-4 py-3 text-center">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($record['areas'] as $area)
                        <tr>
                            <th class="border-b border-r border-slate-200 px-4 py-3 text-left font-semibold">{{ $area['name'] }}</th>
                            @foreach ([1, 2, 3, 4] as $quarter)<td class="border-b border-r border-slate-200 px-3 py-3 text-center">{{ $area['quarters'][$quarter] ?? '—' }}</td>@endforeach
                            <td class="border-b border-r border-slate-200 px-3 py-3 text-center font-bold">{{ $area['final'] ?? '—' }}</td>
                            <td class="border-b border-slate-200 px-4 py-3 text-center font-bold {{ $area['remarks'] === 'PASSED' ? 'text-emerald-700' : 'text-red-700' }}">{{ $area['remarks'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No grades are available for this school year.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-900 text-white">
                        <th colspan="5" class="px-4 py-3 text-left">General Average</th>
                        <td class="px-3 py-3 text-center font-bold">{{ $record['general_average'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-center font-bold">{{ $record['remarks'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</main>

@if($requestId && !($issuedDocument?->status === 'valid') && in_array(auth()->user()->role, ['admin', 'records_officer']))
<div id="finalize-modal" class="hidden fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-black">Finalize Official Form 138?</h2>
        <p class="mt-3 text-sm leading-6 text-slate-600">This creates the permanent document ID and QR, embeds the X.509 signature, hashes the final signed PDF, and stores it as an immutable official copy. Corrections require revoke and reissue.</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" onclick="document.getElementById('finalize-modal').classList.add('hidden')" class="rounded-xl border border-slate-300 px-4 py-2 font-bold text-slate-700">Cancel</button>
            <form method="POST" action="{{ route('school-forms.f138.finalize') }}" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent='Finalizing official document…';">
                @csrf
                <input type="hidden" name="student" value="{{ $student->student_number ?: $student->lrn }}">
                <input type="hidden" name="school_year" value="{{ $enrollment->school_year }}">
                <input type="hidden" name="request_id" value="{{ $requestId }}">
                <button class="rounded-xl bg-emerald-600 px-5 py-2 font-black text-white disabled:opacity-60">Confirm &amp; Issue</button>
            </form>
        </div>
    </div>
</div>
@endif
@include('partials.loading-overlay')
</body>
</html>
