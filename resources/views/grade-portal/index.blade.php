@extends('layouts.app')

@section('title', 'Grade Portal')
@section('page_title', 'Grade Portal')
@section('page_subtitle', 'Upload and manage student Form 138 records')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Upload Section -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden h-fit">
        <div class="p-10 border-b border-slate-50 flex items-center gap-6 bg-slate-50/50">
            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-blue-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Upload Form 138</h2>
                <p class="text-sm font-medium text-slate-400 mt-1">Store new academic records for students</p>
            </div>
        </div>
        
        <div class="p-10">
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-4 animate-fade-in shadow-sm">
                    <div class="w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-sm font-bold">{{ session('success') }}</span>
                </div>
            @endif

            <form id="grade-upload" action="{{ route('grade-portal.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.2em] px-1">Student Number</label>
                        <input type="text" name="student_number" class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 transition-all shadow-inner" required placeholder="e.g. 2024-0001">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.2em] px-1">Student Name</label>
                        <input type="text" name="name" class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 transition-all shadow-inner" required placeholder="Full Name">
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">Upload Entries</h3>
                    </div>
                    
                    <div id="upload-entries" class="space-y-6">
                        <div class="bg-slate-50/50 p-8 rounded-3xl border border-slate-100 space-y-6 relative group transition-all hover:bg-white hover:shadow-xl hover:shadow-blue-500/5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">School Year</label>
                                    <select name="school_years[]" class="w-full bg-white border-slate-200 rounded-xl px-5 py-3 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 transition-all" required>
                                        <option value="">Select school year</option>
                                        @foreach (config('academics.school_years', []) as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Reference File (Excel/PDF/Image)</label>
                                    <input type="file" name="files[]" class="w-full bg-white border-slate-200 rounded-xl px-5 py-2.5 text-xs font-bold text-slate-500 focus:ring-2 focus:ring-blue-500 transition-all" accept=".xlsx,.xls,.pdf,.jpg,.jpeg,.png" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" onclick="addUploadField()" class="w-full inline-flex items-center justify-center gap-3 text-xs font-black text-blue-600 hover:text-blue-700 transition-all px-6 py-4 rounded-2xl border-2 border-dashed border-blue-100 hover:border-blue-300 bg-blue-50/30 hover:bg-blue-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add another School Year
                    </button>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-black py-5 rounded-[1.5rem] shadow-2xl shadow-slate-900/10 transition-all flex items-center justify-center gap-3 uppercase tracking-[0.2em] text-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Upload Academic Records
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- History/Viewer Section -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden flex flex-col">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50">
            <div class="flex items-center gap-6 mb-8">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-slate-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Grade Viewer</h2>
                    <p class="text-sm font-medium text-slate-400 mt-1">Search and manage existing records</p>
                </div>
            </div>
            
            <form id="student-finder" action="{{ route('grade-portal.index') }}" method="GET">
                <div class="flex gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" 
                               name="search_student" 
                               value="{{ request('search_student') }}" 
                               class="w-full pl-14 pr-6 py-4 bg-white border-slate-200 rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 transition-all shadow-sm" 
                               placeholder="Search Student Number...">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-10 py-4 rounded-2xl font-black shadow-lg shadow-blue-500/20 transition-all text-xs uppercase tracking-widest">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <div class="flex-1 p-10">
            @if(request('search_student'))
                @if($student)
                    <div class="flex items-center gap-4 mb-8 p-6 bg-blue-50/50 rounded-3xl border border-blue-100/50">
                        <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/20">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Active Record</p>
                            <p class="text-lg font-black text-slate-800 mt-0.5">{{ $student->name }} <span class="text-slate-400 font-medium ml-2 text-sm">({{ $student->student_number }})</span></p>
                        </div>
                    </div>

                    @if($uploads->count() > 0)
                        <div class="overflow-hidden rounded-[1.5rem] border border-slate-100 shadow-sm">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                                        <th class="px-8 py-5">School Year</th>
                                        <th class="px-8 py-5">File Name</th>
                                        <th class="px-8 py-5 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach($uploads as $upload)
                                        <tr class="hover:bg-slate-50/50 transition-all group">
                                            <td class="px-8 py-5">
                                                <span class="text-sm font-black text-slate-700">{{ $upload->school_year }}</span>
                                            </td>
                                            <td class="px-8 py-5">
                                                <p class="text-xs font-medium text-slate-500 truncate max-w-[200px]" title="{{ $upload->original_filename }}">
                                                    {{ $upload->original_filename }}
                                                </p>
                                            </td>
                                            <td class="px-8 py-5">
                                                <div class="flex items-center justify-center gap-3">
                                                    @php
                                                        $extension = pathinfo($upload->file_path, PATHINFO_EXTENSION);
                                                        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png']);
                                                        $isPdf = strtolower($extension) === 'pdf';
                                                    @endphp
                                                    
                                                    <a href="{{ route('grade-portal.uploads.view', $upload) }}"
                                                       class="w-10 h-10 flex items-center justify-center {{ $isPdf ? 'text-red-600 bg-red-50 hover:bg-red-600' : ($isImage ? 'text-blue-600 bg-blue-50 hover:bg-blue-600' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-600') }} hover:text-white rounded-xl transition-all shadow-sm" 
                                                       title="View/Download {{ strtoupper($extension) }}" 
                                                       target="_blank" rel="noopener noreferrer">
                                                        @if($isPdf)
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                                        @elseif($isImage)
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                        @else
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                        @endif
                                                    </a>
                                                    <form action="{{ route('grade-portal.delete', $upload->id) }}" 
                                                          method="POST" 
                                                          data-confirm="Delete grade upload '{{ $upload->original_filename }}' (record #{{ $upload->id }})? This cannot be undone."
                                                          onsubmit="return confirm(this.dataset.confirm);"
                                                          class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="w-10 h-10 flex items-center justify-center text-red-400 bg-red-50 hover:bg-red-500 hover:text-white rounded-xl transition-all shadow-sm">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-empty-state heading="No grade records for this student" description="No Form 138 files have been uploaded for this student yet." :action-url="in_array(auth()->user()->role, ['admin', 'registrar'], true) ? '#grade-upload' : null" action-label="Upload a grade record" />
                    @endif
                @else
                    <x-empty-state heading="No matching student found" description="Check the student number and try again, or clear your search." :action-url="route('grade-portal.index')" action-label="Clear student search" />
                @endif
            @else
                <x-empty-state heading="Find a student’s grade records" description="Enter a student number to view their uploaded Form 138 files." action-url="#student-finder" action-label="Search for a student" />
            @endif
        </div>
    </div>
</div>

<script>
    function addUploadField() {
        const container = document.getElementById('upload-entries');
        const div = document.createElement('div');
        div.className = 'bg-slate-50/50 p-8 rounded-3xl border border-slate-100 space-y-6 relative group animate-fade-in hover:bg-white hover:shadow-xl hover:shadow-blue-500/5 transition-all';
        div.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">School Year</label>
                    <select name="school_years[]" class="w-full bg-white border-slate-200 rounded-xl px-5 py-3 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 transition-all" required>
                        <option value="">Select school year</option>
                        @foreach (config('academics.school_years', []) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-4">
                    <div class="flex-1 space-y-2">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Excel File (F138)</label>
                        <input type="file" name="files[]" class="w-full bg-white border-slate-200 rounded-xl px-5 py-2.5 text-xs font-bold text-slate-500 focus:ring-2 focus:ring-blue-500 transition-all" accept=".xlsx,.xls" required>
                    </div>
                    <button type="button" onclick="this.closest('.bg-slate-50\\/50').remove()" class="self-end w-12 h-12 flex items-center justify-center text-red-400 bg-red-50 hover:bg-red-500 hover:text-white rounded-xl transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(div);
    }
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fade-in 0.3s ease-out forwards;
    }
</style>
@endsection
