@extends('layouts.app')

@section('title', 'Good Moral Maker')
@section('page_title', 'Good Moral Certification')
@section('page_subtitle', 'Generate and preview good moral character certifications')

@php
    $docRequest = null;
    if(request('request_id')) {
        $docRequest = \App\Models\RequestDocument::find(request('request_id'));
    }
    $isProcessed = $docRequest && in_array($docRequest->status, ['processed', 'ready_to_release', 'completed']);
@endphp

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex items-center gap-6">
            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-indigo-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Certificate Maker</h2>
                <p class="text-sm font-medium text-slate-400 mt-1">Fill in the details and complete the checklist below</p>
            </div>
        </div>

        <form action="{{ route('good-moral.generate') }}" method="POST" target="_blank" class="p-10 space-y-10" id="goodMoralForm">
            @csrf
            @if(request('request_id'))
                <input type="hidden" name="request_id" value="{{ request('request_id') }}">
            @endif
            
            <!-- Student Selection -->
            <div class="space-y-4">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 text-[10px]">01</span>
                    Student Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="student_name" class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Student Name</label>
                        <input type="text" name="student_name" id="student_name" required list="student_list"
                            value="{{ $selectedStudent ?? '' }}"
                            class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                            placeholder="Type or select student name">
                        <datalist id="student_list">
                            @foreach($students as $student)
                                <option value="{{ $student->name }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="space-y-2">
                        <label for="purpose" class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Purpose (Optional)</label>
                        <input type="text" name="purpose" id="purpose"
                            value="{{ $selectedPurpose ?? '' }}"
                            class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                            placeholder="e.g. Scholarship, Employment">
                    </div>
                </div>
            </div>

            <!-- Requirement Checklist -->
            <div class="space-y-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 text-[10px]">02</span>
                    Requirement Checklist
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $requirements = [
                            'No pending disciplinary cases',
                            'Clearance from Finance Office',
                            'Clearance from Library',
                            'Completed residency requirement'
                        ];
                    @endphp

                    @foreach($requirements as $index => $req)
                    <label class="flex items-center gap-4 p-5 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-indigo-100 cursor-pointer transition-all group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="checklist[]" value="{{ $index }}" required
                                {{ $isProcessed ? 'checked' : '' }}
                                class="w-6 h-6 rounded-lg border-2 border-slate-200 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 transition-all cursor-pointer">
                        </div>
                        <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-600 transition-colors">{{ $req }}</span>
                    </label>
                    @endforeach
                </div>
                
                @error('checklist')
                    <p class="text-xs text-red-500 font-bold mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="pt-10 border-t border-slate-50 flex flex-col md:flex-row gap-4 justify-between items-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                    * All requirements must be checked to enable generation
                </p>
                <div class="flex flex-wrap gap-4">
                    @if(request('request_id'))
                    <button type="button" id="submitRegistrarBtn"
                        class="px-8 py-4 bg-emerald-600 text-white text-xs font-black rounded-2xl shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition-all uppercase tracking-widest flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        Submit to Registrar
                    </button>
                    @endif

                    <button type="submit" name="preview" value="1" id="previewBtn"
                        class="px-8 py-4 bg-white text-slate-600 text-xs font-black rounded-2xl border border-slate-200 hover:bg-slate-50 transition-all uppercase tracking-widest flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Preview PDF
                    </button>
                    <button type="submit" id="generateBtn"
                        class="px-10 py-4 bg-indigo-600 text-white text-xs font-black rounded-2xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all uppercase tracking-widest flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download PDF
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('goodMoralForm');
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        const previewBtn = document.getElementById('previewBtn');
        const generateBtn = document.getElementById('generateBtn');
        const submitRegistrarBtn = document.getElementById('submitRegistrarBtn');

        function updateButtons() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            previewBtn.disabled = !allChecked;
            generateBtn.disabled = !allChecked;
            if (submitRegistrarBtn) submitRegistrarBtn.disabled = !allChecked;
            
            if (allChecked) {
                previewBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                generateBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                if (submitRegistrarBtn) submitRegistrarBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                previewBtn.classList.add('opacity-50', 'cursor-not-allowed');
                generateBtn.classList.add('opacity-50', 'cursor-not-allowed');
                if (submitRegistrarBtn) submitRegistrarBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        if (submitRegistrarBtn) {
            submitRegistrarBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to submit this to the Registrar for approval?')) {
                    form.action = "{{ route('good-moral.submit') }}";
                    form.target = "_self";
                    form.submit();
                }
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateButtons);
        });

        // Initial state
        updateButtons();
    });
</script>
@endsection
