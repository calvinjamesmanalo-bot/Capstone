<details class="mt-4 rounded-xl border border-slate-200 bg-white p-3 text-left">
    <summary class="cursor-pointer text-xs font-bold text-indigo-700">Status timeline ({{ $histories->count() }})</summary>
    <ol class="mt-3 space-y-3 border-l-2 border-indigo-100 pl-4">
        @forelse($histories as $entry)
            <li class="relative text-xs text-slate-700">
                <span class="absolute -left-[22px] top-1 h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                <p class="font-semibold">{{ $entry->from_status ? str_replace('_', ' ', ucfirst($entry->from_status)).' → ' : '' }}{{ str_replace('_', ' ', ucfirst($entry->to_status)) }}</p>
                <p class="mt-1 text-slate-500">{{ $entry->created_at?->format('M d, Y · h:i A') }}</p>
                @if($staff)
                    <p class="mt-1 text-slate-500">By: {{ $entry->changedBy?->display_name ?? 'System / existing record' }}</p>
                    @if($entry->remarks)
                        <p class="mt-1 whitespace-pre-wrap">Remarks: {{ $entry->remarks }}</p>
                    @endif
                @endif
            </li>
        @empty
            <li class="text-slate-500">No recorded status changes yet.</li>
        @endforelse
    </ol>
</details>
