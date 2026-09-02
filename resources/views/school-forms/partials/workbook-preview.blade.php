<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-600">
        <span class="rounded-full bg-[#000638] px-3 py-1.5 text-white">{{ $upload->school_year }}</span>
        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5">{{ $upload->level }} &middot; {{ $upload->section }}</span>
        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-amber-900">{{ ['First', 'Second', 'Third', 'Fourth'][$upload->grading_period - 1] ?? "Period {$upload->grading_period}" }} grading</span>
    </div>

    @if(count($sheets) > 1)
        <div class="flex gap-2 overflow-x-auto border-b border-slate-200 pb-3" role="tablist" aria-label="Workbook sheets">
            @foreach($sheets as $sheet)
                <button type="button" data-sheet-tab="{{ $loop->index }}" class="shrink-0 rounded-lg px-4 py-2 text-xs font-bold transition {{ $loop->first ? 'bg-[#000638] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $sheet['name'] }}
                </button>
            @endforeach
        </div>
    @endif

    @foreach($sheets as $sheet)
        <section data-sheet-panel="{{ $loop->index }}" @class(['hidden' => !$loop->first])>
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-[#000638]">{{ $sheet['name'] }}</p>
                    <p class="text-xs text-slate-500">Read-only preview &middot; {{ count($sheet['rows']) }} populated rows</p>
                </div>
                @if($sheet['truncated'])
                    <span class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800">Showing first 200 rows</span>
                @endif
            </div>

            <div class="max-h-[55vh] overflow-auto rounded-xl border border-slate-300 bg-white shadow-inner">
                <table class="min-w-max border-separate border-spacing-0 text-xs text-slate-800">
                    <thead class="sticky top-0 z-20">
                        <tr>
                            <th class="sticky left-0 z-30 min-w-12 border-b border-r border-slate-300 bg-slate-200 px-3 py-2 text-center text-slate-500">#</th>
                            @foreach($sheet['columns'] as $column)
                                <th class="min-w-28 border-b border-r border-slate-300 bg-slate-100 px-3 py-2 text-center font-bold text-slate-600">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sheet['rows'] as $row)
                            <tr class="hover:bg-amber-50/50">
                                <th class="sticky left-0 z-10 border-b border-r border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-500">{{ $row['index'] }}</th>
                                @foreach($sheet['columns'] as $column)
                                    <td class="max-w-72 whitespace-pre-wrap border-b border-r border-slate-200 px-3 py-2 align-top">{{ $row['cells'][$column] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($sheet['columns']) + 1 }}" class="px-6 py-10 text-center text-slate-500">No readable cell values were found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    <p class="flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-500">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg>
        This is a fast data preview. Complex Excel formatting, formulas, charts, and images may look different in Microsoft Excel.
    </p>
</div>
