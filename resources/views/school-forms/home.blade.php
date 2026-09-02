<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Forms Maker</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) @vite(['resources/css/app.css', 'resources/js/app.js']) @endif
</head>
<body class="min-h-screen bg-[#f6f7f9] font-sans text-slate-950">
<header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-5 sm:px-8">
    <a href="{{ route('school-forms.home') }}" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-600 text-sm font-bold text-white">SF</span><span><span class="block font-bold">School Forms Maker</span><span class="block text-xs text-slate-500">Student report card workspace</span></span></a>
    <a href="{{ route('school-forms.records') }}" class="rounded-lg bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700">Grade Sheet Records</a>
</div></header>
<main class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
    <div class="mb-9 max-w-2xl"><p class="mb-2 text-sm font-semibold text-blue-600">Simple school form workspace</p><h1 id="page-title" class="text-3xl font-bold sm:text-4xl">F138 Maker</h1><p id="page-description" class="mt-3 leading-7 text-slate-600">Generate a student's F138 from uploaded grade sheets.</p></div>
    @if ($errors->any()) <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="grid border-b border-slate-200 sm:grid-cols-2">
            <button type="button" id="f138-tab" class="bg-blue-50 p-6 text-left sm:border-r"><span class="mb-4 grid h-9 w-9 place-items-center rounded-lg bg-blue-600 text-sm font-bold text-white">1</span><h2 class="text-lg font-bold">F138 Maker</h2><p class="mt-1 text-sm text-slate-500">Create a progress report card.</p></button>
            <button type="button" id="f137-tab" class="p-6 text-left hover:bg-slate-50"><span class="mb-4 grid h-9 w-9 place-items-center rounded-lg bg-slate-900 text-sm font-bold text-white">2</span><h2 class="text-lg font-bold">F137 Maker</h2><p class="mt-1 text-sm text-slate-500">Prepare a permanent student record.</p></button>
        </div>
        <div class="p-6 sm:p-8">
            <form id="f138-section" method="GET" action="{{ route('school-forms.f138.preview') }}" target="_blank">
                <div class="mb-5"><h3 class="font-bold">Create F138 for a student</h3><p class="mt-1 text-sm text-slate-500">Choose the exact school year that the F138 will be made from.</p></div>
                <div class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-5 sm:grid-cols-2">
                    <label><span class="mb-2 block text-xs font-bold uppercase text-slate-500">Student number or LRN</span><input name="student" value="{{ request('student', '2020-0001') }}" maxlength="40" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 font-semibold" required></label>
                    <label><span class="mb-2 block text-xs font-bold uppercase text-slate-500">School year</span><select name="school_year" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3" required><option value="">Choose school year</option>@foreach (['2020-2021','2021-2022','2022-2023'] as $year)<option value="{{ $year }}" @selected(request('school_year') === $year)>{{ $year }}</option>@endforeach</select></label>
                </div>
                <div class="mt-5 flex justify-end"><button class="rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white">Generate F138</button></div>
            </form>
            <form id="f137-section" class="hidden" method="GET" action="{{ route('school-forms.f137.preview') }}" target="_blank">
                <div class="mb-5"><h3 class="font-bold">Create F137 for a student</h3><p class="mt-1 text-sm text-slate-500">All available uploaded school-year records for the student will be compiled automatically.</p></div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <label><span class="mb-2 block text-xs font-bold uppercase text-slate-500">Student number or LRN</span><input name="student" placeholder="Enter student identifier" maxlength="40" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 font-semibold" required></label>
                </div>
                <div class="mt-5 flex flex-wrap justify-end gap-3"><a href="{{ route('school-forms.f137.template') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700">Download blank template</a><button class="rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white">Preview generated F137</button></div>
            </form>
        </div>
    </div>
</main>
<script>
const f138Tab=document.getElementById('f138-tab'),f137Tab=document.getElementById('f137-tab'),f138=document.getElementById('f138-section'),f137=document.getElementById('f137-section'),title=document.getElementById('page-title'),description=document.getElementById('page-description');
function selectMaker(type){const is138=type==='f138';f138.classList.toggle('hidden',!is138);f137.classList.toggle('hidden',is138);f138Tab.classList.toggle('bg-blue-50',is138);f137Tab.classList.toggle('bg-blue-50',!is138);title.textContent=is138?'F138 Maker':'F137 Maker';description.textContent=is138?"Generate a student's F138 from uploaded grade sheets.":"Compile all available school-year records into a student's permanent record."} f138Tab.onclick=()=>selectMaker('f138');f137Tab.onclick=()=>selectMaker('f137');
selectMaker(@json(request('form') === 'f137' ? 'f137' : 'f138'));
</script>
@include('partials.loading-overlay')
</body></html>
