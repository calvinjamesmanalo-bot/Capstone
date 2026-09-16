@extends('layouts.app')

@section('title', 'Form 138 Maker')
@section('page_title', 'Form 138 (Report Card)')
@section('page_subtitle', 'Generate and preview student report cards for a specific school year')

@section('content')
<div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-8">
    <div class="flex-1">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex items-center gap-6">
            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-indigo-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Form 138 Maker</h2>
                <p class="text-sm font-medium text-slate-400 mt-1">Fill in the grades for a single school year</p>
            </div>
        </div>

        <form action="{{ route('form-138.generate') }}" method="POST" target="_blank" class="p-10 space-y-10" id="form138Form">
            @csrf
            @if(request('request_id'))
                <input type="hidden" name="request_id" value="{{ request('request_id') }}">
            @endif
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Student Selection -->
                <div class="space-y-2">
                    <label for="student_name" class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Student Name</label>
                    <input type="text" name="student_name" id="student_name" required list="student_list"
                        value="{{ $selectedStudent ?? '' }}"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="Type or select student">
                    <datalist id="student_list">
                        @foreach($students as $student)
                            <option value="{{ $student->name }}">
                        @endforeach
                    </datalist>
                </div>

                <!-- School Year -->
                <div class="space-y-2">
                    <label for="school_year" class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">School Year</label>
                    <input type="text" name="school_year" id="school_year" required
                        value="{{ $schoolYear ?? '2023-2024' }}"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800"
                        placeholder="e.g. 2023-2024">
                </div>

                <!-- Grade Level -->
                <div class="space-y-2">
                    <label for="grade_level" class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Grade Level</label>
                    <input type="text" name="grade_level" id="grade_level" required
                        value="{{ $gradeLevel ?? '' }}"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800"
                        placeholder="e.g. Grade 7">
                </div>
            </div>

            <!-- Grades Table -->
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 text-[10px]">02</span>
                        Subject Ratings
                    </h3>
                    <button type="button" onclick="addSubjectRow()" class="text-xs font-black text-indigo-600 uppercase tracking-widest flex items-center gap-2 hover:text-indigo-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add Subject
                    </button>
                </div>

                <div class="overflow-x-auto border border-slate-100 rounded-3xl">
                    <table class="w-full text-left" id="gradesTable">
                        <thead>
                            <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="px-6 py-4">Subject</th>
                                <th class="px-6 py-4 text-center">Q1</th>
                                <th class="px-6 py-4 text-center">Q2</th>
                                <th class="px-6 py-4 text-center">Q3</th>
                                <th class="px-6 py-4 text-center">Q4</th>
                                <th class="px-6 py-4 text-center">Final</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @if(!empty($grades))
                                @foreach($grades as $index => $grade)
                                <tr>
                                    <td class="px-6 py-3">
                                        <input type="text" name="subjects[{{ $index }}][name]" value="{{ $grade->subject_name }}" required
                                            class="w-full bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q1]" value="{{ $grade->q1 }}" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q2]" value="{{ $grade->q2 }}" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q3]" value="{{ $grade->q3 }}" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q4]" value="{{ $grade->q4 }}" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][final]" value="{{ $grade->final_grade }}" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-black text-indigo-600 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <!-- Default Rows -->
                                @php
                                    $defaultSubjects = ['Filipino', 'English', 'Mathematics', 'Science', 'Araling Panlipunan', 'MAPEH', 'TLE', 'EsP'];
                                @endphp
                                @foreach($defaultSubjects as $index => $subject)
                                <tr>
                                    <td class="px-6 py-3">
                                        <input type="text" name="subjects[{{ $index }}][name]" value="{{ $subject }}" required
                                            class="w-full bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q1]" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q2]" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q3]" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][q4]" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" name="subjects[{{ $index }}][final]" step="0.01"
                                            class="w-16 mx-auto bg-transparent border-none font-black text-indigo-600 focus:ring-0 p-0 text-sm text-center">
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-10 border-t border-slate-50 flex flex-col md:flex-row gap-4 justify-between items-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                    * Make sure all grades are correctly entered before generating
                </p>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:gap-4">
                    @if(request('request_id'))
                    <button type="button" id="submitRegistrarBtn"
                        class="flex w-full items-center justify-center gap-3 rounded-2xl bg-emerald-600 px-8 py-4 text-xs font-black text-white shadow-lg shadow-emerald-500/20 transition-all hover:bg-emerald-700 sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        Submit to Registrar
                    </button>
                    @endif

                    <button type="submit" id="previewBtn"
                        class="flex w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white px-8 py-4 text-xs font-black text-slate-600 transition-all hover:bg-slate-50 sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Preview PDF
                    </button>

                    <button type="submit" name="download" value="1" id="generateBtn"
                        class="flex w-full items-center justify-center gap-3 rounded-2xl bg-indigo-600 px-10 py-4 text-xs font-black text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-700 sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Draft PDF
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Reference Files Sidebar -->
    @if(!empty($referenceFiles) && count($referenceFiles) > 0)
    <div class="lg:w-80 space-y-6">
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Reference Files</h3>
                <p class="text-[10px] font-bold text-slate-400 mt-1">Uploaded Form 138 copies</p>
            </div>
            <div class="p-6 space-y-4">
                @foreach($referenceFiles as $file)
                    @php
                        $extension = pathinfo($file->file_path, PATHINFO_EXTENSION);
                        $isPdf = strtolower($extension) === 'pdf';
                        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png']);
                    @endphp
                    <div class="group">
                        <a href="{{ route('grade-portal.uploads.view', $file) }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-xl hover:shadow-indigo-500/5 transition-all">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $isPdf ? 'bg-red-50 text-red-500' : ($isImage ? 'bg-blue-50 text-blue-500' : 'bg-emerald-50 text-emerald-500') }}">
                                @if($isPdf)
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                @elseif($isImage)
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                @endif
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-[11px] font-black text-slate-700 truncate uppercase tracking-tight">{{ $file->school_year }}</p>
                                <p class="text-[9px] font-bold text-slate-400 truncate">{{ $file->original_filename }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 bg-indigo-50 rounded-[2rem] border border-indigo-100/50">
            <h4 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-2">Tip</h4>
            <p class="text-[11px] text-slate-600 font-medium leading-relaxed">Open these files in a new tab to use them as reference while encoding the grades below.</p>
        </div>
    </div>
    @endif
</div>

<script>
    let subjectCount = {{ !empty($grades) ? count($grades) : 8 }};

    function addSubjectRow() {
        const tbody = document.querySelector('#gradesTable tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-6 py-3">
                <input type="text" name="subjects[${subjectCount}][name]" required
                    class="w-full bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm" placeholder="Subject Name">
            </td>
            <td class="px-6 py-3">
                <input type="number" name="subjects[${subjectCount}][q1]" step="0.01"
                    class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
            </td>
            <td class="px-6 py-3">
                <input type="number" name="subjects[${subjectCount}][q2]" step="0.01"
                    class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
            </td>
            <td class="px-6 py-3">
                <input type="number" name="subjects[${subjectCount}][q3]" step="0.01"
                    class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
            </td>
            <td class="px-6 py-3">
                <input type="number" name="subjects[${subjectCount}][q4]" step="0.01"
                    class="w-16 mx-auto bg-transparent border-none font-bold text-slate-700 focus:ring-0 p-0 text-sm text-center">
            </td>
            <td class="px-6 py-3">
                <input type="number" name="subjects[${subjectCount}][final]" step="0.01"
                    class="w-16 mx-auto bg-transparent border-none font-black text-indigo-600 focus:ring-0 p-0 text-sm text-center">
            </td>
            <td class="px-6 py-3 text-right">
                <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        subjectCount++;
    }

    function removeRow(btn) {
        if (confirm('Remove this subject?')) {
            btn.closest('tr').remove();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form138Form');
        const submitRegistrarBtn = document.getElementById('submitRegistrarBtn');
        const previewBtn = document.getElementById('previewBtn');
        const generateBtn = document.getElementById('generateBtn');

        if (previewBtn) {
            previewBtn.addEventListener('click', function() {
                form.action = "{{ route('form-138.generate') }}";
                form.target = "_blank";
                // Remove download name if present
                const downloadInput = form.querySelector('input[name="download"]');
                if(downloadInput) downloadInput.remove();
            });
        }

        if (generateBtn) {
            generateBtn.addEventListener('click', function() {
                form.action = "{{ route('form-138.generate') }}";
                form.target = "_blank";
                // Ensure download input exists
                if(!form.querySelector('input[name="download"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'download';
                    input.value = '1';
                    form.appendChild(input);
                }
            });
        }

        if (submitRegistrarBtn) {
            submitRegistrarBtn.addEventListener('click', function() {
                if (confirm('Submit this report card data to the Registrar for approval?')) {
                    form.action = "{{ route('form-138.submit') }}";
                    form.target = "_self";
                    form.submit();
                }
            });
        }
    });
</script>
@endsection
