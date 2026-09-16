@extends('layouts.app')

@section('title', 'Diploma Maker')
@section('page_title', 'Diploma Management')
@section('page_subtitle', 'Generate digital diploma or process physical copy pickup')

@php
    $isProcessed = $docRequest && in_array($docRequest->status, ['processed', 'ready_to_release', 'completed']);
    $isPickup = $docRequest && str_contains(strtoupper($docRequest->remarks), 'MODE: PICKUP');
@endphp

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex items-center gap-6">
            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-amber-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Diploma Processor</h2>
                <p class="text-sm font-medium text-slate-400 mt-1">Select delivery mode and complete requirements</p>
            </div>
        </div>

        <form action="{{ route('diploma.generate') }}" method="POST" target="_blank" class="p-10 space-y-10" id="diplomaForm">
            @csrf
            @if($docRequest)
                <input type="hidden" name="request_id" value="{{ $docRequest->id }}">
            @endif
            
            <!-- Delivery Mode -->
            <div class="space-y-4">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 text-[10px]">01</span>
                    Select Option
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-center gap-4 p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-amber-100 cursor-pointer transition-all group relative overflow-hidden" id="modeGeneratorLabel">
                        <input type="radio" name="delivery_mode" value="generator" class="hidden" {{ !$isPickup ? 'checked' : '' }}>
                        <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-slate-400 group-[.selected]:text-amber-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <div>
                            <span class="block text-sm font-black text-slate-800 uppercase tracking-tight">Generate Digital</span>
                            <span class="block text-[10px] font-bold text-slate-400">Create PDF for printing</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-4 p-6 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-emerald-100 cursor-pointer transition-all group relative overflow-hidden" id="modePickupLabel">
                        <input type="radio" name="delivery_mode" value="pickup" class="hidden" {{ $isPickup ? 'checked' : '' }}>
                        <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-slate-400 group-[.selected]:text-emerald-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <div>
                            <span class="block text-sm font-black text-slate-800 uppercase tracking-tight">Physical Pickup</span>
                            <span class="block text-[10px] font-bold text-slate-400">Student will claim at school</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Student Information (Conditional) -->
            <div class="space-y-4" id="generatorFields">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 text-[10px]">02</span>
                    Diploma Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="student_name" class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Student Name</label>
                        <input type="text" name="student_name" id="student_name" required list="student_list"
                            value="{{ $selectedStudent ?? '' }}"
                            class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-amber-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                            placeholder="Type student name">
                        <datalist id="student_list">
                            @foreach($students as $student)
                                <option value="{{ $student->name }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="space-y-2">
                        <label for="course" class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Course / Program</label>
                        <input type="text" name="course" id="course" required
                            class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-amber-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                            placeholder="e.g. GENERAL SECONDARY EDUCATION">
                    </div>
                    <div class="space-y-2">
                        <label for="graduation_date" class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Graduation Date</label>
                        <input type="date" name="graduation_date" id="graduation_date" required
                            class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-amber-500 transition-all font-bold text-slate-800">
                    </div>
                </div>
            </div>

            <!-- Requirement Checklist -->
            <div class="space-y-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 text-[10px]">03</span>
                    Clearance Checklist
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $requirements = [
                            'Completed all academic units',
                            'Financial clearance (No balance)',
                            'Library clearance (Returned books)',
                            'Final grades submitted and verified'
                        ];
                    @endphp

                    @foreach($requirements as $index => $req)
                    <label class="flex items-center gap-4 p-5 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-amber-100 cursor-pointer transition-all group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="checklist[]" value="{{ $index }}" required
                                {{ $isProcessed ? 'checked' : '' }}
                                class="w-6 h-6 rounded-lg border-2 border-slate-200 text-amber-600 focus:ring-amber-500 focus:ring-offset-0 transition-all cursor-pointer">
                        </div>
                        <span class="text-sm font-bold text-slate-600 group-hover:text-amber-600 transition-colors">{{ $req }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-10 border-t border-slate-50 flex flex-col md:flex-row gap-4 justify-between items-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                    * Complete all checks to proceed
                </p>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:gap-4">
                    @if($docRequest)
                    <button type="button" id="submitRegistrarBtn"
                        class="flex w-full items-center justify-center gap-3 rounded-2xl bg-emerald-600 px-8 py-4 text-xs font-black text-white shadow-lg shadow-emerald-500/20 transition-all hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        Submit for Approval
                    </button>
                    @endif

                    <div id="generatorActions" class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:gap-4">
                        <button type="submit" name="preview" value="1" id="previewBtn"
                            class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white px-8 py-4 text-xs font-black text-slate-600 transition-all hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                            Preview PDF
                        </button>
                        <button type="submit" id="generateBtn"
                            class="flex w-full items-center justify-center gap-3 rounded-2xl bg-amber-600 px-10 py-4 text-xs font-black text-white shadow-lg shadow-amber-500/20 transition-all hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                            Download PDF
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('diplomaForm');
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        const modeRadios = form.querySelectorAll('input[name="delivery_mode"]');
        const generatorFields = document.getElementById('generatorFields');
        const generatorActions = document.getElementById('generatorActions');
        const previewBtn = document.getElementById('previewBtn');
        const generateBtn = document.getElementById('generateBtn');
        const submitRegistrarBtn = document.getElementById('submitRegistrarBtn');

        function updateUI() {
            const mode = form.querySelector('input[name="delivery_mode"]:checked').value;
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            // Highlight selected mode
            document.getElementById('modeGeneratorLabel').classList.toggle('border-amber-500', mode === 'generator');
            document.getElementById('modeGeneratorLabel').classList.toggle('selected', mode === 'generator');
            document.getElementById('modePickupLabel').classList.toggle('border-emerald-500', mode === 'pickup');
            document.getElementById('modePickupLabel').classList.toggle('selected', mode === 'pickup');

            // Show/Hide generator fields
            generatorFields.style.display = mode === 'generator' ? 'block' : 'none';
            generatorActions.style.display = mode === 'generator' ? 'flex' : 'none';

            // Disable/Enable inputs
            const genInputs = generatorFields.querySelectorAll('input');
            genInputs.forEach(input => input.required = mode === 'generator');

            // Button states
            const canProceed = allChecked;
            previewBtn.disabled = !canProceed;
            generateBtn.disabled = !canProceed;
            if (submitRegistrarBtn) submitRegistrarBtn.disabled = !canProceed;

            [previewBtn, generateBtn, submitRegistrarBtn].forEach(btn => {
                if (btn) {
                    if (canProceed) {
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }
            });
        }

        modeRadios.forEach(radio => radio.addEventListener('change', updateUI));
        checkboxes.forEach(cb => cb.addEventListener('change', updateUI));

        if (submitRegistrarBtn) {
            submitRegistrarBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to submit this diploma request for approval?')) {
                    form.action = "{{ route('diploma.submit') }}";
                    form.target = "_self";
                    form.submit();
                }
            });
        }

        // Initial state
        updateUI();
    });
</script>
@endsection
