@extends('layouts.app')

@section('title', 'F137 Maker')
@section('page_title', 'Form 137 Generator')
@section('page_subtitle', 'Generate and manage student permanent records')

@section('content')
<style>
    .form-137-page .text-indigo-600 { color: #062b63 !important; }
    .form-137-page .bg-indigo-600 { background-color: #062b63 !important; }
    .form-137-page .bg-indigo-50 { background-color: #fff8e7 !important; }
    .form-137-page .border-indigo-100 { border-color: #f1d38a !important; }
    .form-137-page .hover\:bg-indigo-50:hover { background-color: #fff3cf !important; }
    .form-137-page .hover\:bg-indigo-700:hover { background-color: #041d45 !important; }
    .form-137-page .hover\:text-indigo-600:hover,
    .form-137-page .hover\:text-indigo-700:hover { color: #062b63 !important; }
    .form-137-page .text-slate-400 { color: #64748b !important; }
    .form-137-page .text-\[10px\] { font-size: 0.75rem !important; }
    .form-137-page .p-10 { padding: 1.5rem !important; }
    .form-137-page .p-20 { padding: 2rem !important; }
    .form-137-page .p-32 { padding: 3rem 1.5rem !important; }
    .form-137-page input:focus { border-color: #062b63 !important; }
</style>
<div class="form-137-page space-y-8">
    <!-- Header Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 border-l-4 border-[#d59b11]">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
                    <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe logo" class="w-14 h-14 object-contain" />
                </div>
                <div>
                    <h2 class="text-2xl font-semibold text-[#062b63]">Form 137 Generator</h2>
                    <p class="text-sm text-slate-600 mt-1">Create an official student permanent record.</p>
                </div>
            </div>

            <form action="{{ route('form-137.index') }}" method="GET" class="flex-1 max-w-md">
                <div class="flex gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" 
                               name="student_number" 
                               value="{{ request('student_number') }}" 
                               class="w-full pl-14 pr-4 py-3 bg-white border-slate-300 rounded-lg text-sm font-medium text-slate-800 focus:ring-2 focus:ring-[#062b63] focus:border-[#062b63] transition-all"
                               placeholder="Enter student number">
                    </div>
                    <button type="submit" class="bg-[#062b63] hover:bg-[#041d45] text-white px-6 py-3 rounded-lg font-semibold transition-colors text-sm">
                        Find student
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('show_preview'))
        <!-- Preview Modal -->
        <div id="previewModal" class="fixed inset-0 z-[70] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closePreviewModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full border border-slate-200">
                    <div class="bg-white px-10 py-6 border-b border-slate-100 flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight" id="modal-title">Form 137 Preview</h3>
                        </div>
                        <button type="button" onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-10">
                        <div class="overflow-auto max-h-[70vh] rounded-3xl border border-slate-100 bg-slate-50 p-8 shadow-inner">
                            @php
                                try {
                                    $tempData = session('temp_encoded_grades');
                                    $student = \App\Models\Student::where('student_number', $tempData['student_number'])->first();
                                    $controller = app(\App\Http\Controllers\Form137Controller::class);
                                    
                                    $reflection = new \ReflectionClass($controller);
                                    $method = $reflection->getMethod('generateSpreadsheet');
                                    $method->setAccessible(true);
                                    $spreadsheet = $method->invoke($controller, $student, $tempData['selected_uploads'] ?? []);
                                    
                                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
                                    echo $writer->generateHTMLHeader(false);
                                    echo $writer->generateSheetData();
                                    echo $writer->generateHTMLFooter();
                                } catch (\Exception $e) {
                                    echo "<div class='p-8 text-red-600 bg-red-50 rounded-3xl border border-red-100 flex items-center gap-4 shadow-sm'>
                                            <div class='w-10 h-10 bg-red-500 rounded-full flex items-center justify-center text-white'>
                                                <svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' />
                                                </svg>
                                            </div>
                                            <span class='font-black text-sm uppercase tracking-widest'>Error generating preview: " . $e->getMessage() . "</span>
                                          </div>";
                                }
                            @endphp
                        </div>
                    </div>
                    <div class="bg-slate-50 px-10 py-6 flex flex-row-reverse gap-4 border-t border-slate-100">
                        <a href="{{ route('form-137.download', ['student_number' => session('temp_encoded_grades')['student_number']]) }}" class="inline-flex items-center gap-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black px-10 py-4 rounded-2xl shadow-lg shadow-emerald-500/20 transition-all text-xs uppercase tracking-widest">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Excel
                        </a>
                        <button type="button" onclick="closePreviewModal()" class="px-8 py-4 text-xs font-black text-slate-500 hover:text-slate-800 transition-colors uppercase tracking-widest">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(request('student_number'))
        @if(isset($student))
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
                <!-- Left Column -->
                <div class="space-y-10">
                    <!-- Student Info Card -->
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-10">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-32 h-32 bg-indigo-600 rounded-[2.5rem] flex items-center justify-center text-white font-black text-4xl shadow-2xl shadow-indigo-500/30 mb-8">
                                {{ substr($student->name, 0, 1) }}
                            </div>
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">{{ $student->name }}</h3>
                            <p class="text-indigo-600 font-black text-sm uppercase tracking-[0.2em] mt-2">{{ $student->student_number }}</p>
                        </div>
                    </div>

                    <!-- Reference Files -->
                    @if(isset($uploads) && $uploads->count() > 0)
                        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                            <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">Reference Files (F138)</h4>
                            </div>
                            <div class="p-8 space-y-4">
                                @foreach($uploads as $upload)
                                    @php
                                        $extension = pathinfo($upload->file_path, PATHINFO_EXTENSION);
                                        $isPdf = strtolower($extension) === 'pdf';
                                        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png']);
                                    @endphp
                                    <div class="flex items-center gap-4 p-4 bg-slate-50 border border-slate-100 rounded-2xl group hover:bg-white hover:shadow-xl hover:shadow-indigo-500/5 transition-all">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $isPdf ? 'bg-red-50 text-red-500' : ($isImage ? 'bg-blue-50 text-blue-500' : 'bg-emerald-50 text-emerald-500') }}">
                                            @if($isPdf)
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                            @elseif($isImage)
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            @else
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">SY {{ $upload->school_year }}</p>
                                            <p class="text-xs font-bold text-slate-400 truncate mt-1">{{ $upload->original_filename }}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            @if(!$isPdf && !$isImage)
                                            <button onclick="showReferencePreview('{{ route('form-137.view-html', $upload->id) }}', 'SY: {{ $upload->school_year }}')" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-indigo-600 bg-white rounded-xl shadow-sm transition-all" title="View Preview">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            @endif
                                            <a href="{{ route('grade-portal.uploads.view', $upload) }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-emerald-600 bg-white rounded-xl shadow-sm transition-all" title="View/Download">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column: Manual Encoding -->
                <div class="xl:col-span-2">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-10 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Grade Encoding</h2>
                                <p class="text-sm font-medium text-slate-400 mt-1">Manual data entry for student record</p>
                            </div>
                            
                            <form action="{{ route('form-137.download', ['student_number' => $student->student_number]) }}" method="GET">
                                @if(isset($uploads))
                                    @foreach($uploads as $upload)
                                        <input type="hidden" name="selected_uploads[]" value="{{ $upload->id }}">
                                    @endforeach
                                @endif
                                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-black py-4 px-8 rounded-2xl shadow-2xl shadow-slate-900/10 transition-all flex items-center gap-3 uppercase tracking-widest text-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Direct Download
                                </button>
                            </form>
                        </div>

                        <div class="p-10">
                            <form action="{{ route('form-137.preview-manual') }}" method="POST" id="manualEncodeForm" class="space-y-10">
                                @csrf
                                <input type="hidden" name="student_number" value="{{ $student->student_number }}">
                                @if(isset($uploads))
                                    @foreach($uploads as $upload)
                                        <input type="hidden" name="selected_uploads[]" value="{{ $upload->id }}">
                                    @endforeach
                                @endif

                                <div id="sy-container" class="space-y-12">
                                    @if(old('data'))
                                        @foreach(old('data') as $idx => $syData)
                                            <div class="sy-section bg-slate-50/30 p-10 rounded-[2.5rem] border border-slate-100 relative group hover:bg-white hover:shadow-2xl hover:shadow-indigo-500/5 transition-all">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                                                    <div class="space-y-2">
                                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] px-1">School Year</label>
                                                        <input type="text" name="data[{{ $idx }}][school_year]" value="{{ $syData['school_year'] }}" class="w-full bg-white border-slate-200 rounded-2xl px-6 py-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm" placeholder="e.g. 2023-2024" required>
                                                    </div>
                                                    <div class="space-y-2">
                                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] px-1">Grade Level</label>
                                                        <div class="flex gap-4">
                                                            <input type="text" name="data[{{ $idx }}][grade_level]" value="{{ $syData['grade_level'] }}" class="w-full bg-white border-slate-200 rounded-2xl px-6 py-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm" placeholder="e.g. Grade 7" required>
                                                            @if($idx > 0)
                                                                <button type="button" onclick="this.closest('.sy-section').remove()" class="w-14 h-14 flex items-center justify-center text-red-400 bg-red-50 hover:bg-red-500 hover:text-white rounded-2xl transition-all shadow-sm" title="Remove Section">
                                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="overflow-hidden rounded-[1.5rem] border border-slate-100 shadow-sm bg-white mb-8">
                                                    <table class="w-full text-sm grade-table">
                                                        <thead>
                                                            <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                                                                <th class="px-6 py-4 text-left w-1/3">Subject Name</th>
                                                                <th class="px-4 py-4 text-center">Q1</th>
                                                                <th class="px-4 py-4 text-center">Q2</th>
                                                                <th class="px-4 py-4 text-center">Q3</th>
                                                                <th class="px-4 py-4 text-center">Q4</th>
                                                                <th class="px-4 py-4 text-center">Final</th>
                                                                <th class="px-6 py-4 text-right"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-50">
                                                            @foreach($syData['subjects'] as $subIdx => $subject)
                                                                <tr class="hover:bg-slate-50/50 transition-all">
                                                                    <td class="px-6 py-3"><input type="text" name="data[{{ $idx }}][subjects][]" value="{{ $subject }}" class="w-full border-none bg-transparent focus:ring-0 text-sm font-bold text-slate-700" placeholder="e.g. MATHEMATICS" required></td>
                                                                    <td class="p-2 text-center"><input type="number" name="data[{{ $idx }}][q1][]" value="{{ $syData['q1'][$subIdx] ?? '' }}" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
                                                                    <td class="p-2 text-center"><input type="number" name="data[{{ $idx }}][q2][]" value="{{ $syData['q2'][$subIdx] ?? '' }}" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
                                                                    <td class="p-2 text-center"><input type="number" name="data[{{ $idx }}][q3][]" value="{{ $syData['q3'][$subIdx] ?? '' }}" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
                                                                    <td class="p-2 text-center"><input type="number" name="data[{{ $idx }}][q4][]" value="{{ $syData['q4'][$subIdx] ?? '' }}" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
                                                                    <td class="p-2 text-center"><input type="number" name="data[{{ $idx }}][final][]" value="{{ $syData['final'][$subIdx] ?? '' }}" class="w-16 h-10 text-center border-none bg-indigo-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black text-indigo-600" min="0" max="100"></td>
                                                                    <td class="px-6 py-3 text-right"><button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500 transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <button type="button" onclick="addRow(this)" class="w-full inline-flex items-center justify-center gap-3 text-[10px] font-black text-indigo-600 hover:text-indigo-700 transition-all px-6 py-4 rounded-2xl border-2 border-dashed border-indigo-100 hover:border-indigo-300 bg-indigo-50/30 hover:bg-indigo-50 uppercase tracking-widest">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Add Subject Row
                                                </button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="flex flex-col md:flex-row gap-6 pt-10 border-t border-slate-50">
                                    <button type="button" onclick="addSYSection()" class="flex-1 bg-white border-2 border-dashed border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-indigo-600 font-black py-5 rounded-3xl transition-all flex items-center justify-center gap-4 uppercase tracking-[0.2em] text-xs">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Add School Year Section
                                    </button>
                                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-3xl shadow-2xl shadow-indigo-600/20 transition-all flex items-center justify-center gap-4 uppercase tracking-[0.2em] text-xs">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Preview Form 137
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="p-20 text-center bg-red-50 rounded-[3rem] border border-red-100">
                <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center text-red-400 mx-auto mb-6 shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-red-600 uppercase tracking-widest">Student Not Found</h3>
                <p class="text-red-400 font-medium mt-2">The student number you entered does not exist in our directory.</p>
            </div>
        @endif
    @else
        <div class="p-32 text-center bg-slate-50/50 rounded-[3rem] border-2 border-dashed border-slate-100/50">
            <div class="w-24 h-24 bg-white rounded-[2rem] flex items-center justify-center text-slate-200 mx-auto mb-8 shadow-sm">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h3 class="text-slate-400 text-lg font-black uppercase tracking-[0.3em]">Load a student record to begin</h3>
            <p class="text-slate-300 font-medium mt-3">Search for a student number in the header above</p>
        </div>
    @endif
</div>

<!-- Reference Preview Modal -->
<div id="referenceModal" class="fixed inset-0 z-[80] hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeReferenceModal()"></div>
        <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-5xl overflow-hidden border border-slate-100">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight" id="refTitle">F138 Reference</h3>
                    <p class="text-xs font-black text-indigo-600 uppercase tracking-widest mt-1" id="refSubtitle"></p>
                </div>
                <button onclick="closeReferenceModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-10">
                <div id="refContent" class="overflow-auto max-h-[60vh] rounded-3xl border border-slate-100 p-8 bg-white shadow-inner">
                    <!-- Preview content injected here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let sectionCount = {{ old('data') ? count(old('data')) : 0 }};

    function addSYSection() {
        const container = document.getElementById('sy-container');
        const idx = sectionCount++;
        const div = document.createElement('div');
        div.className = 'sy-section bg-slate-50/30 p-10 rounded-[2.5rem] border border-slate-100 relative group animate-fade-in hover:bg-white hover:shadow-2xl hover:shadow-indigo-500/5 transition-all';
        div.innerHTML = `
            <button type="button" onclick="this.closest('.sy-section').remove()" class="absolute top-8 right-8 w-12 h-12 flex items-center justify-center text-red-400 bg-red-50 hover:bg-red-500 hover:text-white rounded-2xl transition-all shadow-sm" title="Remove Section">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] px-1">School Year</label>
                    <input type="text" name="data[${idx}][school_year]" class="w-full bg-white border-slate-200 rounded-2xl px-6 py-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm" placeholder="e.g. 2023-2024" required>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] px-1">Grade Level</label>
                    <input type="text" name="data[${idx}][grade_level]" class="w-full bg-white border-slate-200 rounded-2xl px-6 py-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm" placeholder="e.g. Grade 7" required>
                </div>
            </div>
            <div class="overflow-hidden rounded-[1.5rem] border border-slate-100 shadow-sm bg-white mb-8">
                <table class="w-full text-sm grade-table">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                            <th class="px-6 py-4 text-left w-1/3">Subject Name</th>
                            <th class="px-4 py-4 text-center">Q1</th>
                            <th class="px-4 py-4 text-center">Q2</th>
                            <th class="px-4 py-4 text-center">Q3</th>
                            <th class="px-4 py-4 text-center">Q4</th>
                            <th class="px-4 py-4 text-center">Final</th>
                            <th class="px-6 py-4 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50"></tbody>
                </table>
            </div>
            <button type="button" onclick="addRow(this)" class="w-full inline-flex items-center justify-center gap-3 text-[10px] font-black text-indigo-600 hover:text-indigo-700 transition-all px-6 py-4 rounded-2xl border-2 border-dashed border-indigo-100 hover:border-indigo-300 bg-indigo-50/30 hover:bg-indigo-50 uppercase tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Subject Row
            </button>
        `;
        container.appendChild(div);
        addRow(div.querySelector('button')); // Add first row automatically
    }

    function addRow(btn) {
        const tbody = btn.closest('.sy-section').querySelector('tbody');
        const idx = btn.closest('.sy-section').querySelector('input[name*="school_year"]').name.match(/\d+/)[0];
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/50 transition-all animate-fade-in';
        tr.innerHTML = `
            <td class="px-6 py-3"><input type="text" name="data[${idx}][subjects][]" class="w-full border-none bg-transparent focus:ring-0 text-sm font-bold text-slate-700" placeholder="e.g. MATHEMATICS" required></td>
            <td class="p-2 text-center"><input type="number" name="data[${idx}][q1][]" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
            <td class="p-2 text-center"><input type="number" name="data[${idx}][q2][]" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
            <td class="p-2 text-center"><input type="number" name="data[${idx}][q3][]" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
            <td class="p-2 text-center"><input type="number" name="data[${idx}][q4][]" class="w-14 h-10 text-center border-none bg-slate-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black" min="0" max="100"></td>
            <td class="p-2 text-center"><input type="number" name="data[${idx}][final][]" class="w-16 h-10 text-center border-none bg-indigo-50 rounded-lg focus:ring-2 focus:ring-indigo-500 text-xs font-black text-indigo-600" min="0" max="100"></td>
            <td class="px-6 py-3 text-right"><button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500 transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button></td>
        `;
        tbody.appendChild(tr);
    }

    function removeRow(btn) {
        const tbody = btn.closest('tbody');
        if (tbody.children.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('At least one subject row is required.');
        }
    }

    function closePreviewModal() {
        const modal = document.getElementById('previewModal');
        if (modal) modal.style.display = 'none';
    }

    async function showReferencePreview(url, subtitle) {
        const modal = document.getElementById('referenceModal');
        const content = document.getElementById('refContent');
        const sub = document.getElementById('refSubtitle');
        
        modal.classList.remove('hidden');
        sub.innerText = subtitle;
        content.innerHTML = '<div class="flex items-center justify-center p-20"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div></div>';
        
        try {
            const response = await fetch(url);
            const html = await response.text();
            content.innerHTML = html;
        } catch (error) {
            content.innerHTML = `<div class="p-8 text-red-600 bg-red-50 rounded-3xl border border-red-100 flex items-center gap-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="font-black text-sm uppercase tracking-widest">Failed to load preview</span>
            </div>`;
        }
    }

    function closeReferenceModal() {
        document.getElementById('referenceModal').classList.add('hidden');
    }

    // Initialize with one section if none exists
    if (sectionCount === 0) {
        addSYSection();
    }
</script>
@endsection
