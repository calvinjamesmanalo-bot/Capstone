@extends('layouts.app')

@section('title', 'Edit User')
@section('page_title', 'Update Account')
@section('page_subtitle', 'Modify account details for ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-indigo-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Account Details</h2>
                    <p class="text-sm font-medium text-slate-400 mt-1">Update the user's information below</p>
                </div>
            </div>
            <a href="{{ route('users.index') }}" class="px-6 py-3 bg-white text-slate-600 text-xs font-black rounded-xl border border-slate-200 hover:bg-slate-50 transition-all uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>

        <form action="{{ route('users.update', $user) }}" method="POST" class="p-10">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Last Name -->
                <div class="space-y-2">
                    <label for="last_name" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Last Name</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="e.g. Dela Cruz">
                    @error('last_name') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- First Name -->
                <div class="space-y-2">
                    <label for="first_name" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">First Name</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="e.g. Juan">
                    @error('first_name') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Middle Name -->
                <div class="space-y-2">
                    <label for="middle_name" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $user->middle_name) }}"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="e.g. Protacio">
                    @error('middle_name') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Suffix -->
                <div class="space-y-2">
                    <label for="suffix" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Suffix (Optional)</label>
                    <input type="text" name="suffix" id="suffix" value="{{ old('suffix', $user->suffix) }}"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="e.g. Jr., III">
                    @error('suffix') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Student Number (Conditional) -->
                <div id="student_number_field" class="space-y-2 {{ old('role', $user->role) == 'student' ? '' : 'hidden' }}">
                    <label for="student_number" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Student Number</label>
                    <input type="text" name="student_number" id="student_number" value="{{ old('student_number', $user->student_number) }}"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="e.g. 2024-0001">
                    @error('student_number') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label for="email" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="e.g. juan@example.com">
                    @error('email') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Role -->
                <div class="space-y-2">
                    <label for="role" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">System Role</label>
                    <select name="role" id="role" required onchange="toggleStudentNumber(this.value)"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 appearance-none">
                        <option value="student" {{ old('role', $user->role) == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="records_officer" {{ old('role', $user->role) == 'records_officer' ? 'selected' : '' }}>Records Officer</option>
                        <option value="registrar" {{ old('role', $user->role) == 'registrar' ? 'selected' : '' }}>Registrar</option>
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrator</option>
                    </select>
                    @error('role') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <script>
                    function toggleStudentNumber(role) {
                        const field = document.getElementById('student_number_field');
                        if (role === 'student') {
                            field.classList.remove('hidden');
                            document.getElementById('student_number').setAttribute('required', 'required');
                        } else {
                            field.classList.add('hidden');
                            document.getElementById('student_number').removeAttribute('required');
                        }
                    }
                    // Initial check
                    toggleStudentNumber(document.getElementById('role').value);
                </script>

                <div class="hidden md:block"></div>

                <div class="md:col-span-2 py-4">
                    <div class="p-6 bg-amber-50 rounded-2xl border border-amber-100 flex items-center gap-4">
                        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-amber-700">Leave the password fields blank if you don't want to change the current password.</p>
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">New Password</label>
                    <input type="password" name="password" id="password"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="••••••••">
                    @error('password') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Confirm Password -->
                <div class="space-y-2">
                    <label for="password_confirmation" class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                        placeholder="••••••••">
                </div>
            </div>

            <div class="mt-12 flex justify-end">
                <button type="submit" class="px-10 py-4 bg-indigo-600 text-white text-xs font-black rounded-2xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all uppercase tracking-widest flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Update Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection