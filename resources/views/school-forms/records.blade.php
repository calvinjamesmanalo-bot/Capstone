<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grade Sheet Records</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root { --fla-navy: #000638; --fla-gold: #ffd22d; }
        .fla-field:focus { border-color: var(--fla-navy); box-shadow: 0 0 0 4px rgba(255, 210, 45, .28); }
        .fla-file::file-selector-button { color: var(--fla-navy); background: #fff8d6; }
        .fla-file:hover::file-selector-button { background: #ffefaa; }
    </style>
</head>
<body class="min-h-screen bg-[#f6f7fb] font-sans text-slate-950">
<header class="border-b border-white/10 bg-[#000638] text-white shadow-lg shadow-slate-950/10">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
        <a href="{{ route('school-forms.home') }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="h-11 w-11 shrink-0 rounded-full bg-white object-contain ring-2 ring-[#ffd22d]">
            <span class="min-w-0">
                <span class="block truncate font-bold">School Forms Maker</span>
                <span class="block truncate text-xs text-[#ffd22d]">Fiat Lux Academe &middot; Academic Records</span>
            </span>
        </a>
        <a href="{{ route('school-forms.home') }}" class="shrink-0 rounded-lg bg-[#ffd22d] px-4 py-2.5 text-sm font-bold text-[#000638] transition hover:bg-[#ffe36f] focus:outline-none focus:ring-4 focus:ring-white/20">
            Back to Form Makers
        </a>
    </div>
</header>

<main class="mx-auto max-w-6xl px-5 py-9 sm:px-8 sm:py-12">
    <div class="mb-9 max-w-2xl">
        <p class="mb-3 inline-flex rounded-full bg-[#fff5c4] px-3 py-1.5 text-xs font-bold text-[#000638]">Class records workspace</p>
        <h1 class="text-3xl font-bold text-[#000638] sm:text-4xl">Grade Sheet Records</h1>
        <p class="mt-3 leading-7 text-slate-600">
            @if(in_array(auth()->user()->role, ['admin', 'registrar', 'records_officer'], true))
                Upload attendance and summary sheets one grading period at a time, then find, preview, download, or manage them by class.
            @else
                Find, preview, and download attendance or summary sheets securely by class.
            @endif
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
            <p>{{ session('status') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold">Please check the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if(in_array(auth()->user()->role, ['admin', 'registrar', 'records_officer'], true))
    <section class="mb-10 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-start gap-4 border-b border-slate-200 p-6 sm:p-8">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#000638] text-[#ffd22d]">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold">Upload grade sheets</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Choose one class and grading period, then attach its attendance and summary workbooks.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('school-forms.grade-sheets.store') }}" enctype="multipart/form-data" class="p-6 sm:p-8">
            @csrf
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center gap-3">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#ffd22d] text-xs font-bold text-[#000638]">1</span>
                    <div>
                        <h3 class="font-bold">Upload details</h3>
                        <p class="text-xs text-slate-500">Select the class and the grading period you are uploading.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">School year</span>
                        <select name="grade_school_year" class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                            <option value="">Select school year</option>
                            @foreach (['2020-2021','2021-2022','2022-2023'] as $option)
                                <option @selected(old('grade_school_year') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Grade level</span>
                        <select name="grade_level" class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                            <option value="">Select grade</option>
                            @foreach (['Grade 1','Grade 2','Grade 3'] as $option)
                                <option @selected(old('grade_level') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Section</span>
                        <select name="grade_section" class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                            <option value="">Select section</option>
                            <option @selected(old('grade_section') === 'Amity')>Amity</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Grading period</span>
                        <select name="grading_period" class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                            <option value="">Select grading</option>
                            @foreach (['First','Second','Third','Fourth'] as $grading)
                                <option value="{{ $loop->iteration }}" @selected((string) old('grading_period') === (string) $loop->iteration)>{{ $grading }} grading</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#ffd22d] text-xs font-bold text-[#000638]">2</span>
                    <div>
                        <h3 class="font-bold">Grading-period files</h3>
                        <p class="text-xs text-slate-500">Attach the two Excel workbooks for the selected grading period.</p>
                    </div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <label class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-[#ffd22d] hover:bg-[#fffdf3]">
                        <span class="mb-3 flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#000638] text-[#ffd22d]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 2v4m8-4v4M3 10h18"/><rect x="3" y="4" width="18" height="17" rx="2"/></svg>
                            </span>
                            <span>
                                <span class="block text-sm font-bold">Attendance sheet</span>
                                <span class="block text-xs text-slate-500">Monthly attendance workbook</span>
                            </span>
                        </span>
                        <input type="file" name="attendance_file" accept=".xlsx" class="fla-file block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:border-r file:border-slate-200 file:px-3 file:py-3 file:text-sm file:font-bold" required>
                    </label>
                    <label class="rounded-xl border border-amber-200 bg-amber-50/40 p-4 transition hover:border-[#ffd22d] hover:bg-[#fff8d6]">
                        <span class="mb-3 flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#ffd22d] text-[#000638]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg>
                            </span>
                            <span>
                                <span class="block text-sm font-bold">Summary sheet</span>
                                <span class="block text-xs text-slate-500">Grades and averages workbook</span>
                            </span>
                        </span>
                        <input type="file" name="summary_file" accept=".xlsx" class="fla-file block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:border-r file:border-slate-200 file:px-3 file:py-3 file:text-sm file:font-bold" required>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 rounded-xl border border-amber-200 bg-[#fff9dc] p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-[#000638]"><strong>2 workbooks per upload.</strong> Other grading periods stay unchanged; only the selected period is added or replaced.</p>
                <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#000638] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#10175a] focus:outline-none focus:ring-4 focus:ring-[#ffd22d]/40">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
                    Upload grading sheets
                </button>
            </div>
        </form>
    </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#000638] text-[#ffd22d]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold">Record Finder</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Select a class to view its attendance and summary sheets separately.</p>
                </div>
            </div>

            <form method="GET" class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label>
                    <span class="sr-only">School year</span>
                    <select name="school_year" class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                        <option value="">School year</option>
                        @foreach ($schoolYears as $option)<option @selected($schoolYear === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">Grade level</span>
                    <select name="level" class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                        <option value="">Grade level</option>
                        @foreach ($levels as $option)<option @selected($level === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">Section</span>
                    <select name="section" class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                        <option value="">Section</option>
                        @foreach ($sections as $option)<option @selected($section === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#000638] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#10175a] focus:outline-none focus:ring-4 focus:ring-[#ffd22d]/40">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    Find records
                </button>
            </form>
        </div>

        @if ($hasSearch)
            <div class="bg-slate-50 p-6 sm:p-8">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-[#000638]">Selected class</p>
                        <h3 class="mt-1 text-xl font-bold">{{ $schoolYear }} <span class="text-slate-300">/</span> {{ $level }} - {{ $section }}</h3>
                    </div>
                    <span class="w-fit rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600">{{ $uploads->count() }} uploaded sheet(s)</span>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <section class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-white/10 bg-[#000638] px-5 py-4 text-white">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#ffd22d] text-[#000638]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 2v4m8-4v4M3 10h18"/><rect x="3" y="4" width="18" height="17" rx="2"/></svg>
                                </span>
                                <div><h4 class="font-bold">Attendance sheets</h4><p class="text-xs text-slate-300">Monthly attendance records</p></div>
                            </div>
                            <span class="grid h-7 min-w-7 place-items-center rounded-full bg-[#ffd22d] px-2 text-xs font-bold text-[#000638]">{{ $attendanceUploads->count() }}</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($attendanceUploads as $upload)
                                <article class="p-5">
                                    <div class="flex items-start gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700">{{ $upload->grading_period }}</span>
                                        <div class="min-w-0">
                                            <p class="break-words text-sm font-bold leading-5">{{ $upload->original_name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ ['First','Second','Third','Fourth'][$upload->grading_period - 1] ?? "Period {$upload->grading_period}" }} grading &middot; Excel workbook</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex flex-wrap items-center gap-2 pl-12">
                                        <button type="button" data-workbook-preview="{{ route('school-forms.uploads.preview', $upload) }}" data-workbook-name="{{ $upload->original_name }}" class="inline-flex items-center gap-2 rounded-lg bg-[#ffd22d] px-3 py-2 text-xs font-bold text-[#000638] transition hover:bg-[#ffe36f]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                            Quick View
                                        </button>
                                        <a href="{{ route('school-forms.uploads.download', $upload) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#000638] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#10175a]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 4v12m0 0 5-5m-5 5-5-5"/><path d="M5 20h14"/></svg>
                                            Download
                                        </a>
                                        @if(in_array(auth()->user()->role, ['admin', 'registrar'], true))
                                        <form method="POST" action="{{ route('school-forms.uploads.destroy', $upload) }}" onsubmit="return confirm('Delete this sheet and all records imported from it? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="p-8 text-center"><p class="font-bold text-slate-700">No attendance sheets</p><p class="mt-1 text-sm text-slate-500">None have been uploaded for this class.</p></div>
                            @endforelse
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-amber-200 bg-[#fff8d6] px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-[#ffd22d] text-[#000638]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg>
                                </span>
                                <div><h4 class="font-bold text-[#000638]">Summary sheets</h4><p class="text-xs text-amber-800">Grades and general averages</p></div>
                            </div>
                            <span class="grid h-7 min-w-7 place-items-center rounded-full bg-[#000638] px-2 text-xs font-bold text-[#ffd22d]">{{ $summaryUploads->count() }}</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($summaryUploads as $upload)
                                <article class="p-5">
                                    <div class="flex items-start gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700">{{ $upload->grading_period }}</span>
                                        <div class="min-w-0">
                                            <p class="break-words text-sm font-bold leading-5">{{ $upload->original_name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ ['First','Second','Third','Fourth'][$upload->grading_period - 1] ?? "Period {$upload->grading_period}" }} grading &middot; Excel workbook</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex flex-wrap items-center gap-2 pl-12">
                                        <button type="button" data-workbook-preview="{{ route('school-forms.uploads.preview', $upload) }}" data-workbook-name="{{ $upload->original_name }}" class="inline-flex items-center gap-2 rounded-lg bg-[#ffd22d] px-3 py-2 text-xs font-bold text-[#000638] transition hover:bg-[#ffe36f]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                            Quick View
                                        </button>
                                        <a href="{{ route('school-forms.uploads.download', $upload) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#000638] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#10175a]">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 4v12m0 0 5-5m-5 5-5-5"/><path d="M5 20h14"/></svg>
                                            Download
                                        </a>
                                        @if(in_array(auth()->user()->role, ['admin', 'registrar'], true))
                                        <form method="POST" action="{{ route('school-forms.uploads.destroy', $upload) }}" onsubmit="return confirm('Delete this sheet and all records imported from it? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="p-8 text-center"><p class="font-bold text-slate-700">No summary sheets</p><p class="mt-1 text-sm text-slate-500">None have been uploaded for this class.</p></div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        @else
            <div class="grid place-items-center px-6 py-12 text-center">
                <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16M6 12h12M9 19h6"/></svg>
                </span>
                <p class="mt-4 font-bold text-slate-700">Choose a class to view its records</p>
                <p class="mt-1 max-w-sm text-sm text-slate-500">Use the school year, grade level, and section filters above.</p>
            </div>
        @endif
    </section>
</main>

<div id="workbook-preview-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6" role="dialog" aria-modal="true" aria-labelledby="workbook-preview-title">
    <div class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-4 bg-[#000638] px-5 py-4 text-white sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#ffd22d] text-[#000638]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4zM4 9h16M9 4v16"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-[#ffd22d]">Excel quick viewer</p>
                    <h2 id="workbook-preview-title" class="truncate font-bold">Workbook preview</h2>
                </div>
            </div>
            <button type="button" data-preview-close class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-white/20 text-white transition hover:bg-white/10" aria-label="Close workbook preview">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div id="workbook-preview-body" class="min-h-0 flex-1 overflow-y-auto bg-white p-4 sm:p-6">
            <div class="grid min-h-64 place-items-center text-center">
                <div>
                    <span class="mx-auto block h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-[#000638] border-r-[#ffd22d]"></span>
                    <p class="mt-4 font-bold text-[#000638]">Opening workbook preview…</p>
                    <p class="mt-1 text-sm text-slate-500">Reading cells securely from the stored Excel file.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-3 sm:px-6">
            <button type="button" data-preview-close class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-100">Close preview</button>
        </div>
    </div>
</div>

<template id="workbook-preview-loading">
    <div class="grid min-h-64 place-items-center text-center">
        <div>
            <span class="mx-auto block h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-[#000638] border-r-[#ffd22d]"></span>
            <p class="mt-4 font-bold text-[#000638]">Opening workbook preview…</p>
            <p class="mt-1 text-sm text-slate-500">Reading cells securely from the stored Excel file.</p>
        </div>
    </div>
</template>

<script>
    (() => {
        const modal = document.getElementById('workbook-preview-modal');
        const title = document.getElementById('workbook-preview-title');
        const body = document.getElementById('workbook-preview-body');
        const loading = document.getElementById('workbook-preview-loading');
        let lastTrigger = null;

        const closePreview = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            lastTrigger?.focus();
        };

        const initializeSheetTabs = () => {
            const tabs = body.querySelectorAll('[data-sheet-tab]');
            const panels = body.querySelectorAll('[data-sheet-panel]');
            tabs.forEach((tab) => tab.addEventListener('click', () => {
                const selected = tab.dataset.sheetTab;
                tabs.forEach((candidate) => {
                    const active = candidate === tab;
                    candidate.setAttribute('aria-selected', active ? 'true' : 'false');
                    candidate.classList.toggle('bg-[#000638]', active);
                    candidate.classList.toggle('text-white', active);
                    candidate.classList.toggle('bg-slate-100', !active);
                    candidate.classList.toggle('text-slate-600', !active);
                });
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.sheetPanel !== selected));
            }));
        };

        document.querySelectorAll('[data-workbook-preview]').forEach((button) => {
            button.addEventListener('click', async () => {
                lastTrigger = button;
                title.textContent = button.dataset.workbookName || 'Workbook preview';
                body.replaceChildren(loading.content.cloneNode(true));
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';

                try {
                    const response = await fetch(button.dataset.workbookPreview, {
                        headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Preview request failed');
                    body.innerHTML = await response.text();
                    initializeSheetTabs();
                } catch (error) {
                    body.innerHTML = '<div class="grid min-h-64 place-items-center text-center"><div><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-red-50 text-red-600"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01"/><path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg></span><p class="mt-4 font-bold text-slate-900">Preview unavailable</p><p class="mt-1 text-sm text-slate-500">Download the workbook and open it in Excel instead.</p></div></div>';
                }
            });
        });

        modal.querySelectorAll('[data-preview-close]').forEach((button) => button.addEventListener('click', closePreview));
        modal.addEventListener('click', (event) => { if (event.target === modal) closePreview(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview(); });
    })();
</script>
@include('partials.loading-overlay')
</body>
</html>
