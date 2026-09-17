@props(['status' => null])

@php
    [$label, $colors] = match ($status) {
        'pending' => ['Pending', 'bg-amber-50 text-amber-800 border-amber-200'],
        'processing' => ['Processing', 'bg-blue-50 text-blue-800 border-blue-200'],
        'processed' => ['Processed', 'bg-purple-50 text-purple-800 border-purple-200'],
        'ready_to_release' => ['Ready for Release', 'bg-indigo-50 text-indigo-800 border-indigo-200'],
        'completed' => ['Released', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
        'rejected' => ['Rejected', 'bg-red-50 text-red-800 border-red-200'],
        default => [is_string($status) && trim($status) !== '' ? ucfirst(str_replace('_', ' ', $status)) : 'Unknown', 'bg-slate-100 text-slate-800 border-slate-200'],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold', $colors]) }}>{{ $label }}</span>
