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
                Upload one or all grading periods in a single batch, then preview, download, or manage the imported class records.
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

    @if (session('grade_sheet_import_result'))
        @php $importResult = session('grade_sheet_import_result'); @endphp
        <div class="mb-6 flex flex-col gap-4 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="font-bold text-[#000638]">Import complete and ready for review</p><p class="mt-1 text-sm text-blue-800">{{ $importResult['uploaded'] }} workbook(s) validated; {{ $importResult['replaced'] }} replaced. Check the generated F138 before printing.</p></div>
            @if ($importResult['preview_url'] ?? null)
                <a href="{{ $importResult['preview_url'] }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-[#000638] px-4 py-2.5 text-sm font-bold text-white">Preview F138</a>
            @endif
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold">Please check the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if(in_array(auth()->user()->role, ['admin', 'registrar', 'records_officer'], true))
    @php
        $uploadYear = old('grade_school_year', $schoolYear);
        $uploadLevel = old('grade_level', $level);
        $uploadSection = old('grade_section', $section === 'Bambi' ? $section : '');
        $uploadMode = old('upload_mode', 'quarterly');
        $quarterlyPeriod = (int) old('quarterly_period', 1);
        $uploadPeriodCount = \App\Support\AcademicPeriod::count($uploadYear);
        $initialSlots = $hasSearch
            ? $uploads->whereIn('grading_period', range(1, $uploadPeriodCount))->keyBy(fn ($upload) => $upload->grading_period.':'.$upload->file_type)
            : collect();
    @endphp
    <section class="mb-10 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-bulk-uploader data-status-url="{{ route('school-forms.grade-sheets.status') }}" data-template-url="{{ route('school-forms.grade-sheets.template', ['type' => '__TYPE__']) }}">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-start sm:justify-between sm:p-8">
            <div class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#000638] text-[#ffd22d]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold">Upload grade sheets</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Select the class once, then upload any combination of summary and attendance sheets.</p>
                </div>
            </div>
            <div class="min-w-52 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center justify-between text-xs font-bold"><span>Class completeness</span><span data-completion-label>{{ $initialSlots->count() }}/{{ $uploadPeriodCount * 2 }}</span></div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"><span data-completion-bar class="block h-full rounded-full bg-emerald-500 transition-all" style="width: {{ ($initialSlots->count() / ($uploadPeriodCount * 2)) * 100 }}%"></span></div>
            </div>
        </div>

        <form method="POST" action="{{ route('school-forms.grade-sheets.store') }}" enctype="multipart/form-data" class="p-6 sm:p-8" data-upload-form>
            @csrf
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center gap-3">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#ffd22d] text-xs font-bold text-[#000638]">1</span>
                    <div><h3 class="font-bold">Class details</h3><p class="text-xs text-slate-500">These details apply to every workbook below.</p></div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label><span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">School year</span>
                        <select name="grade_school_year" data-class-field class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                            <option value="">Select school year</option>
                            @foreach (config('academics.school_years', []) as $option)<option @selected($uploadYear === $option)>{{ $option }}</option>@endforeach
                        </select>
                    </label>
                    <label><span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Grade level</span>
                        <select name="grade_level" data-class-field class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                            <option value="">Select grade</option>
                            @foreach (config('academics.grade_level_groups', []) as $group => $gradeLevels)
                                <optgroup label="{{ $group }}">
                                    @foreach ($gradeLevels as $option)
                                        <option @selected($uploadLevel === $option)>{{ $option }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </label>
                    <label><span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Section</span>
                        <select name="grade_section" data-class-field class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition" required>
                            <option value="">Select section</option><option @selected($uploadSection === 'Bambi')>Bambi</option>
                        </select>
                    </label>
                </div>
                <div class="mt-5 border-t border-slate-200 pt-5">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">How are you uploading?</p>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 bg-white p-4 has-[:checked]:border-[#000638] has-[:checked]:ring-2 has-[:checked]:ring-[#ffd22d]">
                            <input type="radio" name="upload_mode" value="quarterly" class="mt-1" data-upload-mode @checked($uploadMode === 'quarterly')>
                            <span><strong class="block text-sm text-[#000638]" data-single-period-title>{{ \App\Support\AcademicPeriod::usesTerms($uploadYear) ? 'Single-term upload' : 'Quarterly upload' }}</strong><span class="mt-1 block text-xs leading-5 text-slate-500" data-single-period-description>{{ \App\Support\AcademicPeriod::usesTerms($uploadYear) ? 'Upload after each term. Only one term is shown.' : 'For the usual upload after each grading period. Only one quarter is shown.' }}</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 bg-white p-4 has-[:checked]:border-[#000638] has-[:checked]:ring-2 has-[:checked]:ring-[#ffd22d]">
                            <input type="radio" name="upload_mode" value="bulk" class="mt-1" data-upload-mode @checked($uploadMode === 'bulk')>
                            <span><strong class="block text-sm text-[#000638]">Whole school year / bulk</strong><span class="mt-1 block text-xs leading-5 text-slate-500">Upload several quarters together when completing or migrating records.</span></span>
                        </label>
                    </div>
                    <label class="mt-4 block max-w-sm" data-quarterly-picker>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500" data-period-picker-label>{{ \App\Support\AcademicPeriod::usesTerms($uploadYear) ? 'Term to upload' : 'Grading period to upload' }}</span>
                        <select name="quarterly_period" data-quarter-period class="fla-field w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition">
                            @foreach (range(1, $uploadPeriodCount) as $period)<option value="{{ $period }}" @selected($quarterlyPeriod === $period)>{{ \App\Support\AcademicPeriod::label($uploadYear, $period) }}</option>@endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="mt-5 rounded-xl border-2 border-dashed border-slate-300 bg-white p-5 text-center transition hover:border-[#ffd22d]" data-bulk-drop>
                <input type="file" accept=".xlsx" multiple class="sr-only" id="bulk-grade-files" data-bulk-files>
                <label for="bulk-grade-files" class="cursor-pointer">
                    <span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-[#fff5c4] text-[#000638]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg></span>
                    <span class="mt-3 block text-sm font-bold text-[#000638]" data-drop-title>Drop the quarter's .xlsx files here or browse</span>
                    <span class="mt-1 block text-xs text-slate-500" data-drop-hint>Summary and attendance filenames are assigned to the selected quarter automatically.</span>
                </label>
                <p class="mt-3 hidden text-xs font-semibold text-amber-700" data-auto-assign-message></p>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#ffd22d] text-xs font-bold text-[#000638]">2</span>
                <div><h3 class="font-bold">Workbook slots</h3><p class="text-xs text-slate-500">Empty slots are left unchanged. Workbook headers are verified before anything is imported.</p></div>
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @foreach (range(1, 4) as $period)
                    <article class="overflow-hidden rounded-xl border border-slate-200 {{ $period > $uploadPeriodCount || ($uploadMode === 'quarterly' && $quarterlyPeriod !== $period) ? 'hidden' : '' }}" data-period-card="{{ $period }}">
                        <header class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <div><p class="font-bold" data-period-label>{{ $period <= $uploadPeriodCount ? \App\Support\AcademicPeriod::label($uploadYear, $period) : "Period {$period}" }}</p><p class="text-xs text-slate-500" data-period-progress>0 of 2 selected</p></div>
                            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600" data-period-state>Incomplete</span>
                        </header>
                        <div class="grid gap-3 p-4 sm:grid-cols-2">
                            @foreach (['summary' => 'Summary', 'attendance' => 'Attendance'] as $type => $label)
                                @php $existingUpload = $initialSlots->get($period.':'.$type); @endphp
                                <div class="rounded-lg border border-slate-200 p-3" data-upload-slot data-period="{{ $period }}" data-type="{{ $type }}" data-existing="{{ $existingUpload ? '1' : '0' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div><p class="text-sm font-bold">{{ $label }}</p><p class="mt-0.5 truncate text-[11px] text-slate-500" data-file-name>{{ $existingUpload?->original_name ?? 'No file selected' }}</p></div>
                                        <span class="rounded-full px-2 py-1 text-[10px] font-bold {{ $existingUpload ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}" data-slot-status>{{ $existingUpload ? 'Stored' : 'Missing' }}</span>
                                    </div>
                                    <input type="file" name="{{ $type }}_files[{{ $period }}]" accept=".xlsx" class="sr-only" id="{{ $type }}-file-{{ $period }}" data-slot-input>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <label for="{{ $type }}-file-{{ $period }}" class="cursor-pointer rounded-md bg-[#000638] px-3 py-2 text-[11px] font-bold text-white">Choose file</label>
                                        <a href="{{ route('school-forms.grade-sheets.template', ['type' => $type, 'school_year' => $uploadYear ?: config('academics.school_years')[0], 'level' => $uploadLevel ?: 'Grade 1', 'section' => $uploadSection ?: 'Bambi', 'period' => $period]) }}" data-template-link data-template-type="{{ $type }}" data-template-period="{{ $period }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-600">Template</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6 hidden rounded-xl border border-amber-300 bg-amber-50 p-4" data-replacement-box>
                <label class="flex cursor-pointer items-start gap-3"><input type="checkbox" name="replace_existing" value="1" class="mt-1 h-4 w-4 rounded border-amber-400" data-replacement-check><span><strong class="block text-sm text-amber-900">Confirm replacement of stored workbook(s)</strong><span class="text-xs leading-5 text-amber-800" data-replacement-text>The selected file will replace an existing slot and rebuild its imported records.</span></span></label>
            </div>

            <div class="mt-6 flex flex-col gap-3 rounded-xl border border-amber-200 bg-[#fff9dc] p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-[#000638]" data-selection-summary><strong>No new files selected.</strong> Existing workbooks will not be changed.</p>
                <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#000638] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#10175a] disabled:cursor-not-allowed disabled:opacity-50" data-upload-submit disabled>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
                    Validate and import
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
                                            <p class="mt-1 text-xs text-slate-500">{{ \App\Support\AcademicPeriod::label($upload->school_year, $upload->grading_period) }} &middot; Excel workbook</p>
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
                                            <p class="mt-1 text-xs text-slate-500">{{ \App\Support\AcademicPeriod::label($upload->school_year, $upload->grading_period) }} &middot; Excel workbook</p>
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
        const root = document.querySelector('[data-bulk-uploader]');
        if (!root) return;

        const form = root.querySelector('[data-upload-form]');
        const classFields = [...root.querySelectorAll('[data-class-field]')];
        const modeFields = [...root.querySelectorAll('[data-upload-mode]')];
        const quarterSelect = root.querySelector('[data-quarter-period]');
        const quarterlyPicker = root.querySelector('[data-quarterly-picker]');
        const periodCards = [...root.querySelectorAll('[data-period-card]')];
        const slots = [...root.querySelectorAll('[data-upload-slot]')];
        const bulkInput = root.querySelector('[data-bulk-files]');
        const dropZone = root.querySelector('[data-bulk-drop]');
        const autoMessage = root.querySelector('[data-auto-assign-message]');
        const replacementBox = root.querySelector('[data-replacement-box]');
        const replacementCheck = root.querySelector('[data-replacement-check]');
        const replacementText = root.querySelector('[data-replacement-text]');
        const submit = root.querySelector('[data-upload-submit]');
        const summary = root.querySelector('[data-selection-summary]');
        const completionLabel = root.querySelector('[data-completion-label]');
        const completionBar = root.querySelector('[data-completion-bar]');
        const dropTitle = root.querySelector('[data-drop-title]');
        const dropHint = root.querySelector('[data-drop-hint]');
        const singlePeriodTitle = root.querySelector('[data-single-period-title]');
        const singlePeriodDescription = root.querySelector('[data-single-period-description]');
        const periodPickerLabel = root.querySelector('[data-period-picker-label]');
        let statusRequest = 0;
        let initialized = false;

        const details = () => ({
            school_year: form.elements.grade_school_year.value,
            level: form.elements.grade_level.value,
            section: form.elements.grade_section.value,
        });
        const classReady = () => Object.values(details()).every(Boolean);
        const usesTerms = () => Number(details().school_year.slice(0, 4)) >= 2026;
        const periodCount = () => usesTerms() ? 3 : 4;
        const periodName = (period) => ['First', 'Second', 'Third', 'Fourth'][period - 1] || `Period ${period}`;
        const periodLabel = (period) => `${periodName(period)} ${usesTerms() ? 'term' : 'grading'}`;
        const currentMode = () => modeFields.find((field) => field.checked)?.value || 'quarterly';
        const selectedQuarter = () => Number(quarterSelect.value || 1);
        const slotFor = (period, type) => Number(period) <= periodCount()
            ? slots.find((slot) => slot.dataset.period === String(period) && slot.dataset.type === type)
            : null;

        const render = () => {
            let selected = 0;
            let replacements = 0;
            let stored = 0;
            slots.forEach((slot) => {
                const input = slot.querySelector('[data-slot-input]');
                const active = Number(slot.dataset.period) <= periodCount();
                input.disabled = !active;
                if (!active) {
                    input.value = '';
                    return;
                }
                const file = input.files?.[0];
                const exists = slot.dataset.existing === '1';
                const status = slot.querySelector('[data-slot-status]');
                const name = slot.querySelector('[data-file-name]');
                if (file) {
                    selected++;
                    if (exists) replacements++;
                    name.textContent = file.name;
                    name.title = file.name;
                    status.textContent = exists ? 'Will replace' : 'Ready';
                    status.className = 'rounded-full px-2 py-1 text-[10px] font-bold ' + (exists ? 'bg-amber-100 text-amber-800' : 'bg-blue-50 text-blue-700');
                } else {
                    if (exists) stored++;
                    name.textContent = slot.dataset.existingName || 'No file selected';
                    name.title = slot.dataset.existingName || '';
                    status.textContent = exists ? 'Stored' : 'Missing';
                    status.className = 'rounded-full px-2 py-1 text-[10px] font-bold ' + (exists ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500');
                }
            });

            root.querySelectorAll('[data-period-card]').forEach((card) => {
                const cardSlots = [...card.querySelectorAll('[data-upload-slot]')];
                const ready = cardSlots.filter((slot) => slot.dataset.existing === '1' || slot.querySelector('[data-slot-input]').files?.length).length;
                card.querySelector('[data-period-progress]').textContent = `${ready} of 2 available`;
                const state = card.querySelector('[data-period-state]');
                state.textContent = ready === 2 ? 'Complete' : ready === 1 ? 'Partial' : 'Incomplete';
                state.className = 'rounded-full px-2.5 py-1 text-xs font-bold ' + (ready === 2 ? 'bg-emerald-100 text-emerald-700' : ready === 1 ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600');
            });

            const totalSlots = periodCount() * 2;
            const available = Math.min(totalSlots, stored + selected);
            completionLabel.textContent = `${available}/${totalSlots}`;
            completionBar.style.width = `${(available / totalSlots) * 100}%`;
            replacementBox.classList.toggle('hidden', replacements === 0);
            replacementText.textContent = `${replacements} selected workbook(s) will replace stored slots and rebuild only those imported records.`;
            if (replacements === 0) replacementCheck.checked = false;
            summary.innerHTML = selected
                ? `<strong>${selected} new workbook(s) selected.</strong> ${replacements ? `${replacements} replacement(s) require confirmation.` : 'No stored workbook will be overwritten.'}`
                : '<strong>No new files selected.</strong> Existing workbooks will not be changed.';
            submit.disabled = selected === 0 || !classReady() || (replacements > 0 && !replacementCheck.checked);
        };

        const clearSelectedFiles = (message) => {
            const hadFiles = slots.some((slot) => slot.querySelector('[data-slot-input]').files?.length);
            slots.forEach((slot) => { slot.querySelector('[data-slot-input]').value = ''; });
            if (hadFiles && message) {
                autoMessage.classList.remove('hidden');
                autoMessage.textContent = message;
            }
        };
        const applyMode = () => {
            const singlePeriod = currentMode() === 'quarterly';
            const previousPeriod = selectedQuarter();
            quarterSelect.replaceChildren(...Array.from({length: periodCount()}, (_, index) => {
                const period = index + 1;
                const option = document.createElement('option');
                option.value = String(period);
                option.textContent = periodLabel(period);
                return option;
            }));
            quarterSelect.value = String(previousPeriod <= periodCount() ? previousPeriod : 1);
            const selectedPeriod = selectedQuarter();
            singlePeriodTitle.textContent = usesTerms() ? 'Single-term upload' : 'Quarterly upload';
            singlePeriodDescription.textContent = usesTerms()
                ? 'Upload after each term. Only one term is shown.'
                : 'For the usual upload after each grading period. Only one quarter is shown.';
            periodPickerLabel.textContent = usesTerms() ? 'Term to upload' : 'Grading period to upload';
            quarterlyPicker.classList.toggle('hidden', !singlePeriod);
            periodCards.forEach((card) => {
                const period = Number(card.dataset.periodCard);
                card.querySelector('[data-period-label]').textContent = periodLabel(period);
                card.classList.toggle('hidden', period > periodCount() || (singlePeriod && period !== selectedPeriod));
            });
            dropTitle.textContent = singlePeriod
                ? `Drop the ${usesTerms() ? "term's" : "quarter's"} .xlsx files here or browse`
                : 'Drop several .xlsx files here or browse';
            dropHint.textContent = singlePeriod
                ? `Summary and attendance filenames are assigned to the selected ${usesTerms() ? 'term' : 'quarter'} automatically.`
                : `Include the ${usesTerms() ? 'term' : 'grading period'} in each filename so it can be assigned automatically.`;
            render();
        };

        const setInputFile = (input, file) => {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
        };
        const detectType = (name) => /attendance|sf\s*2/i.test(name) ? 'attendance' : /summary|grade|sf\s*1/i.test(name) ? 'summary' : null;
        const detectPeriod = (name) => {
            const patterns = [/(?:first|1st|q1|quarter\s*1|grading\s*1)/i, /(?:second|2nd|q2|quarter\s*2|grading\s*2)/i, /(?:third|3rd|q3|quarter\s*3|grading\s*3)/i, /(?:fourth|4th|q4|quarter\s*4|grading\s*4)/i];
            const index = patterns.findIndex((pattern) => pattern.test(name.replace(/[_-]+/g, ' ')));
            return index < 0 ? null : index + 1;
        };
        const assignFiles = (files) => {
            let assigned = 0;
            const unmatched = [];
            [...files].forEach((file) => {
                const type = detectType(file.name);
                const period = currentMode() === 'quarterly' ? selectedQuarter() : detectPeriod(file.name);
                const slot = type && period ? slotFor(period, type) : null;
                if (!slot || slot.querySelector('[data-slot-input]').files?.length) {
                    unmatched.push(file.name);
                    return;
                }
                setInputFile(slot.querySelector('[data-slot-input]'), file);
                assigned++;
            });
            autoMessage.classList.remove('hidden');
            autoMessage.textContent = `${assigned} file(s) assigned automatically.` + (unmatched.length ? ` ${unmatched.length} could not be identified or its slot was already selected; choose those manually.` : ' Review the slots before importing.');
            render();
        };

        const updateTemplateLinks = () => {
            if (!classReady()) return;
            root.querySelectorAll('[data-template-link]').forEach((link) => {
                const query = new URLSearchParams({...details(), period: link.dataset.templatePeriod});
                link.href = root.dataset.templateUrl.replace('__TYPE__', link.dataset.templateType) + '?' + query.toString();
            });
        };
        const refreshStatus = async () => {
            updateTemplateLinks();
            if (!classReady()) {
                slots.forEach((slot) => { slot.dataset.existing = '0'; slot.dataset.existingName = ''; });
                render();
                return;
            }
            const requestId = ++statusRequest;
            try {
                const response = await fetch(root.dataset.statusUrl + '?' + new URLSearchParams(details()), {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
                if (!response.ok) throw new Error('Status request failed');
                const data = await response.json();
                if (requestId !== statusRequest) return;
                slots.forEach((slot) => {
                    const existing = data.slots[`${slot.dataset.period}:${slot.dataset.type}`];
                    slot.dataset.existing = existing ? '1' : '0';
                    slot.dataset.existingName = existing?.name || '';
                });
            } catch (error) {
                slots.forEach((slot) => { slot.dataset.existing = '0'; slot.dataset.existingName = ''; });
            }
            render();
        };

        slots.forEach((slot) => {
            slot.dataset.existingName = slot.querySelector('[data-file-name]').textContent.trim() === 'No file selected' ? '' : slot.querySelector('[data-file-name]').textContent.trim();
            slot.querySelector('[data-slot-input]').addEventListener('change', render);
        });
        replacementCheck.addEventListener('change', render);
        modeFields.forEach((field) => field.addEventListener('change', () => {
            if (!field.checked) return;
            clearSelectedFiles('Selected files were cleared because the upload mode changed.');
            applyMode();
        }));
        quarterSelect.addEventListener('change', () => {
            clearSelectedFiles('Selected files were cleared because the grading period changed.');
            applyMode();
        });
        classFields.forEach((field) => field.addEventListener('change', () => {
            if (initialized && slots.some((slot) => slot.querySelector('[data-slot-input]').files?.length)) {
                slots.forEach((slot) => { slot.querySelector('[data-slot-input]').value = ''; });
                autoMessage.classList.remove('hidden');
                autoMessage.textContent = 'Selected files were cleared because the class destination changed.';
            }
            initialized = true;
            applyMode();
            refreshStatus();
        }));
        bulkInput.addEventListener('change', () => assignFiles(bulkInput.files));
        ['dragenter', 'dragover'].forEach((name) => dropZone.addEventListener(name, (event) => { event.preventDefault(); dropZone.classList.add('border-[#ffd22d]', 'bg-[#fffdf3]'); }));
        ['dragleave', 'drop'].forEach((name) => dropZone.addEventListener(name, (event) => { event.preventDefault(); dropZone.classList.remove('border-[#ffd22d]', 'bg-[#fffdf3]'); }));
        dropZone.addEventListener('drop', (event) => assignFiles(event.dataTransfer.files));
        form.addEventListener('submit', (event) => {
            if (submit.disabled) event.preventDefault();
            else { submit.disabled = true; submit.textContent = 'Validating workbooks…'; }
        });
        applyMode();
        refreshStatus();
    })();
</script>

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
