<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiat Lux Academe Document Request Hub - @yield('title', 'Dashboard')</title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('fla-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', savedTheme ? savedTheme === 'dark' : prefersDark);
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --fla-navy: #000638; --fla-gold: #ffd22d; --fla-ink: #10152f; }
        body { font-family: 'Inter', sans-serif; background: #f6f7fb; }
        html.dark { color-scheme: dark; }
        html.dark body { background: #080d1a; }
        html.dark .page-content { color: #e2e8f0; }
        html.dark main { background: #080d1a; }
        html.dark header,
        html.dark footer { background: #101827 !important; border-color: #273449 !important; }
        html.dark header h2,
        html.dark header [class*="text-[#000638]"] { color: #f8fafc !important; }
        html.dark header [class*="bg-slate-50"] { background: #182235 !important; border-color: #334155 !important; }
        html.dark .page-content [class~="bg-white"],
        html.dark .page-content [class*="bg-slate-50"],
        html.dark .page-content [class*="bg-gray-50"] { background-color: #111b2d !important; }
        html.dark .page-content [class*="bg-slate-100"],
        html.dark .page-content [class*="bg-gray-100"] { background-color: #1e293b !important; }
        html.dark .page-content [class*="border-slate-"],
        html.dark .page-content [class*="border-gray-"] { border-color: #334155 !important; }
        html.dark .page-content [class*="text-[#000638]"],
        html.dark .page-content [class*="text-slate-900"],
        html.dark .page-content [class*="text-slate-800"],
        html.dark .page-content [class*="text-gray-900"],
        html.dark .page-content [class*="text-gray-800"] { color: #f1f5f9 !important; }
        html.dark .page-content [class*="text-slate-700"],
        html.dark .page-content [class*="text-slate-600"],
        html.dark .page-content [class*="text-gray-700"],
        html.dark .page-content [class*="text-gray-600"] { color: #cbd5e1 !important; }
        html.dark .page-content [class*="text-slate-500"],
        html.dark .page-content [class*="text-gray-500"] { color: #94a3b8 !important; }
        html.dark .page-content input,
        html.dark .page-content select,
        html.dark .page-content textarea { background-color: #0f172a !important; border-color: #475569 !important; color: #f8fafc !important; }
        html.dark .page-content input::placeholder,
        html.dark .page-content textarea::placeholder { color: #64748b; }
        html.dark .page-content table tbody tr { border-color: #334155 !important; }
        html.dark .page-content table tbody tr:hover { background-color: #182235 !important; }
        html.dark .page-content table thead th { color: #94a3b8 !important; }
        .theme-toggle-icon-sun { display: none; }
        html.dark .theme-toggle-icon-sun { display: block; }
        html.dark .theme-toggle-icon-moon { display: none; }
        .sidebar-link.active {
            background-color: var(--fla-gold);
            color: var(--fla-navy);
        }
        .sidebar-link:not(.active):hover {
            background: rgba(255, 255, 255, 0.09) !important;
            color: white;
        }
        .page-content {
            color: #0f172a;
            width: 100%;
            max-width: none;
        }
        .page-content > * {
            width: 100%;
            max-width: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        .page-content .p-10 {
            padding: 1.5rem !important;
        }
        .page-content .px-10 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }
        .page-content .py-10 {
            padding-top: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }
        .page-content .gap-10 {
            gap: 1.5rem !important;
        }
        .page-content .space-y-10 > :not([hidden]) ~ :not([hidden]),
        .page-content .space-y-12 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 1.5rem !important;
        }
        .page-content [class~="font-black"] {
            font-weight: 600 !important;
        }
        .page-content [class~="uppercase"] {
            text-transform: none !important;
        }
        .page-content [class~="tracking-widest"],
        .page-content [class*="tracking-["] {
            letter-spacing: 0 !important;
        }
        .page-content [class*="rounded-3xl"],
        .page-content [class*="rounded-[2.5rem]"],
        .page-content [class*="rounded-[3rem]"] {
            border-radius: 1rem !important;
        }
        .page-content [class*="shadow-2xl"],
        .page-content [class*="shadow-xl"],
        .page-content [class*="shadow-lg"] {
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
        }
        .page-content table thead th {
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            color: #475569 !important;
        }
        .page-content table td,
        .page-content table th {
            vertical-align: top;
        }
    </style>
</head>
<body class="text-slate-900">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-[#000638] text-slate-200 flex flex-col fixed h-full z-50 border-r border-[#10175a]">
            <!-- Brand -->
            <div class="px-5 py-5 flex items-center gap-3 border-b border-white/10">
                <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe seal" class="w-12 h-12 rounded-full bg-white object-contain ring-2 ring-[#ffd22d]">
                <div>
                    <h1 class="text-white font-bold text-base leading-tight">FIAT LUX ACADEME</h1>
                    <p class="text-xs text-[#ffd22d] mt-1">Document Request Hub</p>
                </div>
            </div>

            <!-- User Profile Section -->
            <div class="px-6 py-5">
                <div class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-white/10">
                    <div class="w-10 h-10 bg-[#ffd22d] rounded-full flex items-center justify-center text-[#000638] font-bold text-base">
                        {{ auth()->user() ? substr(auth()->user()->display_name, 0, 1) : 'G' }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-white text-sm font-semibold truncate">{{ auth()->user()->display_name ?? 'Guest User' }}</p>
                        <p class="text-xs text-slate-300 mt-0.5 capitalize">{{ str_replace('_', ' ', auth()->user()->role ?? 'Visitor') }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-6 space-y-1 overflow-y-auto pb-8 custom-scrollbar text-base">
                @php
                    $role = auth()->user()->role ?? 'admin'; // Default to admin for now if not logged in
                @endphp

                <p class="px-4 mb-3 mt-4 text-xs font-semibold text-slate-400 uppercase tracking-wide">Main Menu</p>
                
                <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('dashboard') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Dashboard</span>
                </a>

                @if(in_array($role, ['admin', 'registrar', 'records_officer']))
                <p class="px-4 mb-3 mt-6 text-xs font-semibold text-slate-400 uppercase tracking-wide">Administrative</p>

                @if($role === 'admin')
                <a href="{{ route('users.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('users.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">User Management</span>
                </a>
                @endif

                <a href="{{ route('requests.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('requests.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">{{ $role === 'registrar' ? 'Approval List' : 'Active Requests' }}</span>
                </a>

                <a href="{{ route('requests.history') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('requests.history') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Request History</span>
                </a>
                @endif

                @if($role === 'student')
                <p class="px-4 mb-3 mt-6 text-xs font-semibold text-slate-400 uppercase tracking-wide">Student Services</p>
                <a href="{{ route('student.request') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('student.request') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Request Document</span>
                </a>

                <a href="{{ route('student.my-requests') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('student.my-requests') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">My Requests</span>
                </a>
                @endif

                @if(in_array($role, ['admin', 'registrar', 'records_officer']))
                <p class="px-4 mb-3 mt-6 text-xs font-semibold text-slate-400 uppercase tracking-wide">Academic Records</p>

                @if(in_array($role, ['admin', 'registrar']))
                <a href="{{ rtrim(config('generator.url'), '/') }}/records" target="_blank" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group hover:bg-slate-800/50">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Grade Sheet Upload</span>
                </a>
                @endif

                @if(in_array($role, ['admin', 'records_officer']))
                <a href="{{ rtrim(config('generator.url'), '/') }}" target="_blank" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group hover:bg-slate-800/50">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Form 137 Maker</span>
                </a>

                <a href="{{ rtrim(config('generator.url'), '/') }}" target="_blank" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group hover:bg-slate-800/50">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Form 138 Maker</span>
                </a>

                <a href="{{ route('certifications.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('certifications.*') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414A1 1 0 0118 9.414V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Certification Maker</span>
                </a>

                <a href="{{ route('good-moral.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('good-moral.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Good Moral Maker</span>
                </a>
                @endif
                @endif

                @if($role === 'admin')
                <p class="px-4 mb-3 mt-6 text-xs font-semibold text-slate-400 uppercase tracking-wide">System</p>

                <a href="{{ route('analytics.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('analytics.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">Data Analytics</span>
                </a>

                <a href="{{ route('logs.index') }}" class="sidebar-link flex items-center gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('logs.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="w-5 h-5 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold">System Logs</span>
                </a>

                <a href="{{ route('settings.index') }}" class="sidebar-link flex items-center justify-between gap-4 px-4 py-3 rounded-xl transition-all hover:text-white group {{ request()->routeIs('settings.index') ? 'active' : 'hover:bg-slate-800/50' }}">
                    <div class="flex items-center gap-4">
                        <div class="w-5 h-5 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            </svg>
                        </div>
                        <span class="text-base font-semibold">Settings</span>
                    </div>
                </a>
                @endif
            </nav>

            <!-- Logout -->
            <div class="p-6 border-t border-white/10">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-4 px-4 py-3 text-base font-semibold text-red-300 rounded-xl hover:text-red-200 hover:bg-red-500/10 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 min-w-0 flex flex-col min-h-screen">
            <!-- Header -->
            <header class="bg-white min-h-20 border-b border-slate-200 flex items-center justify-between gap-6 px-8 py-4 sticky top-0 z-40">
                <div>
                    <h2 class="text-xl font-bold text-[#000638]">@yield('page_title')</h2>
                    <p class="text-sm text-slate-500 mt-0.5">@yield('page_subtitle')</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" data-theme-toggle class="w-11 h-11 shrink-0 rounded-xl border border-slate-200 bg-slate-50 text-[#000638] flex items-center justify-center hover:bg-slate-100 transition-colors" aria-label="Switch to dark mode" title="Switch color theme">
                        <svg class="theme-toggle-icon-moon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        <svg class="theme-toggle-icon-sun w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364-.707-.707M6.343 6.343l-.707-.707m12.728 0-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </button>
                @if(in_array($role, ['admin', 'registrar', 'records_officer']))
                    <div class="hidden sm:flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5" aria-label="Current date and time">
                        <div class="w-9 h-9 rounded-lg bg-[#000638] text-[#ffd22d] flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="min-w-[170px]">
                            <p id="fla-current-date" class="text-xs font-semibold text-slate-500">{{ now()->format('l, F j, Y') }}</p>
                            <p id="fla-current-time" class="text-sm font-bold text-[#000638]">{{ now()->format('h:i:s A') }}</p>
                        </div>
                    </div>
                @endif
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6 lg:p-8 page-content">
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
            <footer class="mt-auto px-8 py-5 text-slate-500 text-xs border-t border-slate-200 bg-white">
                <p>&copy; {{ date('Y') }} Fiat Lux Academe Document Request Hub</p>
            </footer>
        </main>
    </div>
    
    @if(in_array($role, ['admin', 'registrar', 'records_officer']))
    <script>
        function updateFiatLuxClock() {
            const now = new Date();
            const date = document.getElementById('fla-current-date');
            const time = document.getElementById('fla-current-time');
            if (date) date.textContent = now.toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            if (time) time.textContent = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        updateFiatLuxClock();
        setInterval(updateFiatLuxClock, 1000);
    </script>
    @endif
    <script>
        (() => {
            const root = document.documentElement;
            const button = document.querySelector('[data-theme-toggle]');
            const syncLabel = () => button?.setAttribute('aria-label', root.classList.contains('dark') ? 'Switch to light mode' : 'Switch to dark mode');
            button?.addEventListener('click', () => {
                root.classList.toggle('dark');
                localStorage.setItem('fla-theme', root.classList.contains('dark') ? 'dark' : 'light');
                syncLabel();
            });
            syncLabel();
        })();
    </script>
    @stack('scripts')
</body>
</html>
