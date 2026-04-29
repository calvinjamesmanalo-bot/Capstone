<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RequestHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-[#f8fafc] flex items-center justify-center min-h-screen p-6">
    <div class="w-full max-w-[440px]">
        <!-- Brand -->
        <div class="flex flex-col items-center mb-10">
            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-500/10 mb-6">
                <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h1 class="text-3xl font-[800] text-slate-900 tracking-tight">Welcome Back</h1>
            <p class="text-slate-500 mt-2 font-medium">Please enter your details to sign in</p>
        </div>

        <!-- Login Card -->
        <div class="glass-effect rounded-[32px] p-10 shadow-2xl shadow-slate-200/50 border border-white">
            <form action="{{ route('login') }}" method="POST" class="space-y-6">
                @csrf
                
                @if ($errors->any())
                    <div class="p-4 bg-red-50 rounded-2xl border border-red-100 mb-6">
                        @foreach ($errors->all() as $error)
                            <p class="text-xs text-red-600 font-bold flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $error }}
                            </p>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">Email Address</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                            </svg>
                        </div>
                        <input type="email" name="email" value="{{ old('email') }}" required 
                            class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 transition-all"
                            placeholder="name@example.com">
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Password</label>
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" name="password" required 
                            class="block w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 transition-all"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center gap-3 px-1 pt-2">
                    <input type="checkbox" name="remember" id="remember" class="w-5 h-5 rounded-lg border-2 border-slate-200 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <label for="remember" class="text-xs font-bold text-slate-500 cursor-pointer select-none">Remember me</label>
                </div>

                <button type="submit" 
                    class="w-full py-4 bg-indigo-600 text-white rounded-2xl text-sm font-black shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all active:scale-[0.98] mt-4 uppercase tracking-widest">
                    Sign In
                </button>
            </form>

            <!-- Quick Access Section -->
            <div class="mt-10 pt-10 border-t border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center mb-6">Quick Login (Testing)</p>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('login.as', 'admin') }}" 
                        class="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-indigo-600 hover:bg-white transition-all group">
                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 mb-2 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Admin</span>
                    </a>
                    <a href="{{ route('login.as', 'student') }}" 
                        class="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-indigo-600 hover:bg-white transition-all group">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 mb-2 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Student</span>
                    </a>
                    <a href="{{ route('login.as', 'registrar') }}" 
                        class="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-indigo-600 hover:bg-white transition-all group">
                        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-2 group-hover:bg-amber-600 group-hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Registrar</span>
                    </a>
                    <a href="{{ route('login.as', 'records_officer') }}" 
                        class="flex flex-col items-center justify-center p-4 bg-slate-50 rounded-2xl border-2 border-transparent hover:border-indigo-600 hover:bg-white transition-all group">
                        <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center text-violet-600 mb-2 group-hover:bg-violet-600 group-hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Records</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center mt-10 text-slate-400 text-xs font-bold">
            &copy; {{ date('Y') }} RequestHub. All rights reserved.
        </p>
    </div>
</body>
</html>
