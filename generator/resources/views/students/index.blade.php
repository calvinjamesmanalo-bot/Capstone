<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grade Sheet Records</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) @vite(['resources/css/app.css', 'resources/js/app.js']) @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
<main class="mx-auto max-w-5xl px-5 py-10">
    <div class="mb-7 flex items-center justify-between gap-4">
        <div><p class="text-sm font-semibold text-blue-600">Class records workspace</p><h1 class="text-3xl font-bold">Grade Sheet Records</h1><p class="mt-2 text-sm text-slate-500">Upload grade sheets and find previously uploaded class files in one place.</p></div>
        <a href="{{ route('home') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold">Back to Form Makers</a>
    </div>
    @if (session('status')) <div class="mb-5 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    <section class="mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5"><h2 class="text-lg font-bold">Grade Sheet Uploader</h2><p class="mt-1 text-sm text-slate-500">Upload attendance and average sheets for all four grading periods.</p></div>
        <form method="POST" action="{{ route('grade-sheets.store') }}" enctype="multipart/form-data" class="p-5">@csrf
            <div class="mb-5 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-3">
                <label><span class="mb-2 block text-xs font-bold uppercase text-slate-500">School year</span><select name="grade_school_years[]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5" required><option value="">Select school year</option><option>2020-2021</option><option>2021-2022</option><option>2022-2023</option></select></label>
                <label><span class="mb-2 block text-xs font-bold uppercase text-slate-500">Grade level</span><select name="grade_levels[]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5" required><option value="">Select grade</option><option>Grade 1</option><option>Grade 2</option><option>Grade 3</option></select></label>
                <label><span class="mb-2 block text-xs font-bold uppercase text-slate-500">Section</span><select name="grade_sections[]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5" required><option value="">Select section</option><option>Amity</option></select></label>
            </div>
            <div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left"><thead class="border-y border-slate-200 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Grading period</th><th class="px-3 py-3">Attendance sheet</th><th class="px-3 py-3">Average sheet</th></tr></thead><tbody class="divide-y divide-slate-100">
            @foreach (['First','Second','Third','Fourth'] as $grading)<tr><th class="px-3 py-4 text-sm">{{ $grading }} grading</th>@foreach (['attendance','average'] as $type)<td class="px-3 py-3"><input type="file" name="{{ $type }}_files[0][{{ $loop->parent->iteration }}]" accept=".xlsx" class="w-full text-sm" required></td>@endforeach</tr>@endforeach
            </tbody></table></div>
            <div class="mt-5 flex justify-end"><button class="rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white">Upload all sheets</button></div>
        </form>
    </section>

    <div class="mb-4"><h2 class="text-lg font-bold">Record Finder</h2><p class="mt-1 text-sm text-slate-500">Find uploaded sheets by class.</p></div>

    <form method="GET" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <select name="school_year" class="rounded-lg border border-slate-300 px-3 py-3" required>
            <option value="">School year</option>@foreach ($schoolYears as $option)<option @selected($schoolYear === $option)>{{ $option }}</option>@endforeach
        </select>
        <select name="level" class="rounded-lg border border-slate-300 px-3 py-3" required>
            <option value="">Grade level</option>@foreach ($levels as $option)<option @selected($level === $option)>{{ $option }}</option>@endforeach
        </select>
        <select name="section" class="rounded-lg border border-slate-300 px-3 py-3" required>
            <option value="">Section</option>@foreach ($sections as $option)<option @selected($section === $option)>{{ $option }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white">Find uploaded sheets</button>
    </form>

    @if ($hasSearch)
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5"><h2 class="text-lg font-bold">{{ $schoolYear }} · {{ $level }} - {{ $section }}</h2><p class="mt-1 text-sm text-slate-500">{{ $uploads->count() }} uploaded sheet(s)</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($uploads as $upload)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div><p class="font-semibold">{{ $upload->original_name }}</p><p class="mt-1 text-xs text-slate-500">Grading period {{ $upload->grading_period }} · {{ $upload->file_type === 'summary' ? 'Summary sheet' : 'Attendance sheet' }}</p></div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('students.uploads.download', $upload) }}" class="rounded-lg border border-blue-300 px-4 py-2 text-sm font-bold text-blue-700">Download</a>
                            <form method="POST" action="{{ route('students.uploads.destroy', $upload) }}" onsubmit="return confirm('Delete this sheet and all records imported from it? This cannot be undone.')">@csrf @method('DELETE')
                                <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-bold text-red-700">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty <div class="p-8 text-center text-slate-500">No uploaded sheets found for this class.</div> @endforelse
            </div>
        </section>
    @else <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">Select a school year, grade level, and section to view uploaded sheets.</div> @endif
</main>
</body></html>
