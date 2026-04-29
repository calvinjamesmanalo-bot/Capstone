<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RequestHub - @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active {
            background-color: #4f46e5;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-72 bg-[#1e293b] text-slate-400 flex flex-col fixed h-full z-50 border-r border-slate-700/50">
            <!-- Brand -->
            <div class="p-8 flex items-center gap-4">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg shadow-white/10">
                    <svg class="w-7 h-7 text-[#1e293b]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-white font-extrabold text-xl tracking-tight">RequestHub</h1>
                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 font-bold">{{ \App\Models\Setting::where('key', 'institution_name')->first()->value ?? 'Fiat Lux Academe' }}</p>
                </div>
            </div>

            <!-- User Profile Section -->
            <div class="px-8 py-6 mb-4">
                <div class="flex items-center gap-4 p-4 bg-slate-800/40 rounded-2xl border border-slate-700/30">
                    <div class="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-indigo-500/20 border-2 border-indigo-400/20">
                        {{ auth()->user() ? substr(auth()->user()->display_name, 0, 1) : 'G' }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-white text-sm font-bold truncate">{{ auth()->user()->display_name ?? 'Guest User' }}</p>
                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest mt-0.5">{{ auth()->user()->role ?? 'Visitor' }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-6 space-y-1 overflow-y-auto pb-8 custom-scrollbar">
                @php
                    $role = auth()->user()->role ?? 'admin'; // Default to admin for now if not logged in
                @endphp

                <p class="px-4 mb-3 mt-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Main Menu</p>
                
                <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('dashboard') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Dashboard</span>
                </a>

                @if(in_array($role, ['admin', 'registrar', 'records_officer']))
                <p class="px-4 mb-3 mt-6 text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Administrative</p>

                @if($role === 'admin')
                <a href="{{ route('users.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('users.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">User Management</span>
                </a>
                @endif

                <a href="{{ route('requests.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('requests.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">{{ $role === 'registrar' ? 'Approval List' : 'Active Requests' }}</span>
                </a>

                <a href="{{ route('requests.history') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('requests.history') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Request History</span>
                </a>
                @endif

                @if($role === 'student')
                <p class="px-4 mb-3 mt-6 text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Student Services</p>
                <a href="{{ route('student.request') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('student.request') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Request Document</span>
                </a>
                @endif

                @if(in_array($role, ['admin', 'registrar', 'records_officer']))
                <p class="px-4 mb-3 mt-6 text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Academic Records</p>

                @if(in_array($role, ['admin', 'registrar']))
                <a href="{{ route('grade-portal.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('grade-portal.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Grade Portal</span>
                </a>
                @endif

                @if(in_array($role, ['admin', 'records_officer']))
                <a href="{{ route('form-137.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('form-137.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Form 137 Maker</span>
                </a>

                <a href="{{ route('good-moral.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('good-moral.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Good Moral Maker</span>
                </a>
                @endif
                @endif

                @if($role === 'admin')
                <p class="px-4 mb-3 mt-6 text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">System</p>

                <a href="{{ route('logs.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('logs.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">System Logs</span>
                </a>

                <a href="{{ route('settings.index') }}" class="sidebar-link flex items-center justify-between gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('settings.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="flex items-center gap-4">
                        <div class="w-5 h-5 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold">Settings</span>
                    </div>
                </a>
                @endif
            </nav>

            <!-- Logout -->
            <div class="p-8 border-t border-slate-700/50">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-4 px-4 py-3 text-sm font-bold text-red-400 rounded-xl hover:text-red-300 hover:bg-red-500/10 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-72 flex flex-col min-h-screen">
            <!-- Header -->
            <header class="bg-white/80 backdrop-blur-md h-24 border-b border-slate-200 flex items-center justify-between px-10 sticky top-0 z-40">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">@yield('page_title')</h2>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">@yield('page_subtitle')</p>
                </div>

                <div class="flex items-center gap-8">
                    <!-- Search -->
                    <div class="relative hidden xl:block">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" placeholder="Search records..." class="pl-11 pr-6 py-3 text-sm border-none bg-slate-100/80 rounded-xl focus:ring-2 focus:ring-indigo-500 w-80 transition-all font-medium">
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Notifications -->
                        <button class="relative p-3 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute top-3 right-3 flex h-2.5 w-2.5 rounded-full bg-red-500 border-2 border-white"></span>
                        </button>

                        <!-- Profile -->
                        <div class="flex items-center gap-4 pl-6 border-l border-slate-200">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-black text-slate-800 leading-none">{{ auth()->user()->display_name }}</p>
                                <p class="text-[10px] text-indigo-600 font-black uppercase tracking-widest mt-1">{{ auth()->user()->role }}</p>
                            </div>
                            <div class="w-11 h-11 bg-slate-900 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-slate-200 uppercase">
                                {{ substr(auth()->user()->display_name, 0, 1) }}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="mt-auto px-8 py-6 text-slate-400 text-xs border-t border-slate-200 flex justify-between">
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::where('key', 'institution_name')->first()->value ?? 'Fiat Lux Academe' }} RequestHub. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-slate-600 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-slate-600 transition-colors">Terms of Service</a>
                </div>
            </footer>
        </main>
    </div>
    
    @stack('scripts')
</body>
</html>