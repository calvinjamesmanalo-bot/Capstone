<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grade Sheet Records</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-[#f6f7f9] font-sans text-slate-950">
<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-sm font-bold text-white">SF</span>
            <span class="min-w-0">
                <span class="block truncate font-bold">School Forms Maker</span>
                <span class="block truncate text-xs text-slate-500">Student report card workspace</span>
            </span>
        </a>
        <a href="{{ route('home') }}" class="shrink-0 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
            Back to Form Makers
        </a>
    </div>
</header>

<main class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
    <div class="mb-9 max-w-2xl">
        <p class="mb-2 text-sm font-semibold text-blue-600">Class records workspace</p>
        <h1 class="text-3xl font-bold sm:text-4xl">Grade Sheet Records</h1>
        <p class="mt-3 leading-7 text-slate-600">Upload attendance and summary sheets one grading period at a time, then find, download, or manage them by class.</p>
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

    <section class="mb-10 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-start gap-4 border-b border-slate-200 p-6 sm:p-8">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold">Upload grade sheets</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Choose one class and grading period, then attach its attendance and summary workbooks.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('grade-sheets.store') }}" enctype="multipart/form-data" class="p-6 sm:p-8">
            @csrf
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center gap-3">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-slate-900 text-xs font-bold text-white">1</span>
                    <div>
                        <h3 class="font-bold">Upload details</h3>
                        <p class="text-xs text-slate-500">Select the class and the grading period you are uploading.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">School year</span>
                        <select name="grade_school_year" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                            <option value="">Select school year</option>
                            @foreach (['2020-2021','2021-2022','2022-2023'] as $option)
                                <option @selected(old('grade_school_year') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Grade level</span>
                        <select name="grade_level" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                            <option value="">Select grade</option>
                            @foreach (['Grade 1','Grade 2','Grade 3'] as $option)
                                <option @selected(old('grade_level') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Section</span>
                        <select name="grade_section" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                            <option value="">Select section</option>
                            <option @selected(old('grade_section') === 'Amity')>Amity</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Grading period</span>
                        <select name="grading_period" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
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
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-slate-900 text-xs font-bold text-white">2</span>
                    <div>
                        <h3 class="font-bold">Grading-period files</h3>
                        <p class="text-xs text-slate-500">Attach the two Excel workbooks for the selected grading period.</p>
                    </div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <label class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                        <span class="mb-3 flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 2v4m8-4v4M3 10h18"/><rect x="3" y="4" width="18" height="17" rx="2"/></svg>
                            </span>
                            <span>
                                <span class="block text-sm font-bold">Attendance sheet</span>
                                <span class="block text-xs text-slate-500">Monthly attendance workbook</span>
                            </span>
                        </span>
                        <input type="file" name="attendance_file" accept=".xlsx" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:border-r file:border-slate-200 file:bg-slate-50 file:px-3 file:py-3 file:text-sm file:font-bold file:text-blue-700 hover:file:bg-blue-50" required>
                    </label>
                    <label class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                        <span class="mb-3 flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-indigo-600 text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg>
                            </span>
                            <span>
                                <span class="block text-sm font-bold">Summary sheet</span>
                                <span class="block text-xs text-slate-500">Grades and averages workbook</span>
                            </span>
                        </span>
                        <input type="file" name="summary_file" accept=".xlsx" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:border-r file:border-slate-200 file:bg-slate-50 file:px-3 file:py-3 file:text-sm file:font-bold file:text-indigo-700 hover:file:bg-indigo-50" required>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 rounded-xl bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-blue-800"><strong>2 workbooks per upload.</strong> Other grading periods stay unchanged; only the selected period is added or replaced.</p>
                <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
                    Upload grading sheets
                </button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-900 text-white">
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
                    <select name="school_year" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                        <option value="">School year</option>
                        @foreach ($schoolYears as $option)<option @selected($schoolYear === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">Grade level</span>
                    <select name="level" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                        <option value="">Grade level</option>
                        @foreach ($levels as $option)<option @selected($level === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">Section</span>
                    <select name="section" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm font-semibold outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                        <option value="">Section</option>
                        @foreach ($sections as $option)<option @selected($section === $option)>{{ $option }}</option>@endforeach
                    </select>
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    Find records
                </button>
            </form>
        </div>

        @if ($hasSearch)
            <div class="bg-slate-50 p-6 sm:p-8">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-blue-600">Selected class</p>
                        <h3 class="mt-1 text-xl font-bold">{{ $schoolYear }} <span class="text-slate-300">/</span> {{ $level }} - {{ $section }}</h3>
                    </div>
                    <span class="w-fit rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600">{{ $uploads->count() }} uploaded sheet(s)</span>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <section class="overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-blue-100 bg-blue-50 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 text-white">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 2v4m8-4v4M3 10h18"/><rect x="3" y="4" width="18" height="17" rx="2"/></svg>
                                </span>
                                <div><h4 class="font-bold">Attendance sheets</h4><p class="text-xs text-blue-700">Monthly attendance records</p></div>
                            </div>
                            <span class="grid h-7 min-w-7 place-items-center rounded-full bg-white px-2 text-xs font-bold text-blue-700">{{ $attendanceUploads->count() }}</span>
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
                                    <div class="mt-4 flex items-center gap-2 pl-12">
                                        <a href="{{ route('students.uploads.download', $upload) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 4v12m0 0 5-5m-5 5-5-5"/><path d="M5 20h14"/></svg>
                                            Download
                                        </a>
                                        <form method="POST" action="{{ route('students.uploads.destroy', $upload) }}" onsubmit="return confirm('Delete this sheet and all records imported from it? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @empty
                                <div class="p-8 text-center"><p class="font-bold text-slate-700">No attendance sheets</p><p class="mt-1 text-sm text-slate-500">None have been uploaded for this class.</p></div>
                            @endforelse
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-indigo-600 text-white">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg>
                                </span>
                                <div><h4 class="font-bold">Summary sheets</h4><p class="text-xs text-indigo-700">Grades and general averages</p></div>
                            </div>
                            <span class="grid h-7 min-w-7 place-items-center rounded-full bg-white px-2 text-xs font-bold text-indigo-700">{{ $summaryUploads->count() }}</span>
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
                                    <div class="mt-4 flex items-center gap-2 pl-12">
                                        <a href="{{ route('students.uploads.download', $upload) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-indigo-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 4v12m0 0 5-5m-5 5-5-5"/><path d="M5 20h14"/></svg>
                                            Download
                                        </a>
                                        <form method="POST" action="{{ route('students.uploads.destroy', $upload) }}" onsubmit="return confirm('Delete this sheet and all records imported from it? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7"/></svg>
                                                Delete
                                            </button>
                                        </form>
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
</body>
</html>
