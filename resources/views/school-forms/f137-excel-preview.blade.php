<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>F137 Excel Preview - {{ $student->name }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) @vite(['resources/css/app.css', 'resources/js/app.js']) @endif
    @include('partials.responsive-foundation')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white px-5 py-4 shadow-sm">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Read-only workbook preview</p>
            <h1 class="mt-1 text-xl font-bold">F137 Excel - {{ $student->name }}</h1>
        </div>
        <button type="button" onclick="window.close()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">Close preview</button>
    </div>
</header>

<main class="mx-auto max-w-7xl space-y-7 px-4 py-6">
    @foreach($sheets as $sheet)
        @php
            $rows = collect($sheet['rows'] ?? [])->take(200);
            $columns = $rows->flatMap(fn ($row) => collect($row['cells'] ?? [])->pluck('column'))->filter()->unique()->sort()->values();
        @endphp
        <section class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="font-bold text-[#000638]">{{ $sheet['name'] ?? 'Worksheet' }}</h2>
                <p class="mt-1 text-xs text-slate-500">Fast data preview. Open the downloaded file in Excel to see exact formatting, merged cells, and print layout.</p>
            </div>
            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-max border-separate border-spacing-0 text-xs">
                    <thead class="sticky top-0 z-20">
                        <tr>
                            <th class="sticky left-0 z-30 border-b border-r border-slate-300 bg-slate-200 px-3 py-2">#</th>
                            @foreach($columns as $column)<th class="min-w-24 border-b border-r border-slate-300 bg-slate-100 px-3 py-2">{{ $column }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php($cells = collect($row['cells'] ?? [])->pluck('value', 'column'))
                            <tr>
                                <th class="sticky left-0 border-b border-r border-slate-300 bg-slate-100 px-3 py-2">{{ $row['index'] }}</th>
                                @foreach($columns as $column)<td class="max-w-72 whitespace-pre-wrap border-b border-r border-slate-200 px-3 py-2">{{ $cells[$column] ?? '' }}</td>@endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</main>
</body>
</html>
