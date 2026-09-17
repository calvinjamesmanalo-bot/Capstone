@extends('layouts.app')

@section('title', 'System Analytics')
@section('page_title', 'Data Analytics')
@section('page_subtitle', 'Insights and trends for document requests')

@section('content')
<div class="max-w-7xl mx-auto space-y-8 pb-10">
    
    <!-- Filters -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-800">Filters</h3>
                <p class="text-sm text-slate-500">Select a month and year</p>
            </div>
        </div>
        
        <form action="{{ route('analytics.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <select name="month" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <option value="all" {{ $month == 'all' ? 'selected' : '' }}>All Months</option>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ sprintf('%02d', $i) }}" {{ $month == sprintf('%02d', $i) ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                    </option>
                @endfor
            </select>

            <select name="year" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-all">
                Apply
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Top Requested Documents -->
        <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Top requested documents</h3>
            <div class="space-y-3">
                @forelse($requestCounts as $item)
                    <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 bg-slate-100 rounded-md flex items-center justify-center text-slate-700 text-xs font-semibold">
                                {{ $loop->iteration }}
                            </div>
                            <span class="text-sm font-medium text-slate-800">{{ $item->document_type }}</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">{{ $item->total }}</span>
                    </div>
                @empty
                    <p class="text-center text-sm text-slate-500 py-8">No data found for this period.</p>
                @endforelse
            </div>
        </div>

        <!-- Monthly Trends Chart -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Monthly request volume ({{ $year }})</h3>
            </div>
            <div class="flex-1 min-h-[300px] relative">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Request status</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach(['pending', 'processing', 'processed', 'ready_to_release', 'completed'] as $status)
                @php
                    $count = $statusCounts->where('status', $status)->first()->total ?? 0;
                @endphp
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between">
                        <x-request-status-badge :status="$status" />
                        <h4 class="text-xl font-semibold text-slate-900">{{ $count }}</h4>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const el = document.getElementById('trendsChart');
        if (!el) return;

        const ctx = el.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Requests',
                    data: @json($monthlyTrends),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>
@endpush
@endsection
