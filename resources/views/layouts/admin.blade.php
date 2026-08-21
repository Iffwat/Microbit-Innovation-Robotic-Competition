<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="mric">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Pengurusan Pertandingan Microbit Robotic Innovation Competition">
    <title>@yield('title', 'Papan Pemuka') — MRIC TMS</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-base-200 min-h-screen" x-data>

<div class="drawer lg:drawer-open">
    <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />

    {{-- Main Content --}}
    <div class="drawer-content flex flex-col min-h-screen">

        {{-- Top Navbar --}}
        <header class="navbar bg-white border-b border-base-300 sticky top-0 z-30 px-4 gap-3">
            <div class="navbar-start gap-2">
                <label for="sidebar-drawer" class="btn btn-ghost btn-sm lg:hidden p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </label>
                <span class="text-base font-bold text-primary lg:hidden">MRIC TMS</span>
            </div>

            <div class="navbar-center hidden lg:flex">
                <h1 class="text-base font-semibold text-base-content/60 tracking-wide">@yield('page-title', 'Papan Pemuka')</h1>
            </div>

            <div class="navbar-end gap-3">
                {{-- Language Toggle --}}
                <div class="join border border-base-300 rounded-lg overflow-hidden">
                    <a href="{{ route('lang.switch', 'ms') }}"
                       class="join-item px-3 py-1.5 text-xs font-semibold transition-colors {{ app()->getLocale() === 'ms' ? 'bg-primary text-white' : 'bg-white text-base-content/60 hover:bg-base-200' }}">MS</a>
                    <a href="{{ route('lang.switch', 'en') }}"
                       class="join-item px-3 py-1.5 text-xs font-semibold transition-colors {{ app()->getLocale() === 'en' ? 'bg-primary text-white' : 'bg-white text-base-content/60 hover:bg-base-200' }}">EN</a>
                </div>

                {{-- Role Badge --}}
                <div class="hidden md:flex items-center gap-2">
                    @if(session('auth_role') === 'master')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            Master Admin
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-secondary/10 text-secondary">
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                            Sukarelawan
                        </span>
                    @endif
                </div>

                {{-- Avatar / Logout --}}
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle">
                        <div class="w-8 h-8 rounded-full {{ session('auth_role') === 'master' ? 'bg-gradient-to-br from-primary to-secondary' : 'bg-gradient-to-br from-secondary to-accent' }} flex items-center justify-center text-white text-xs font-bold">
                            {{ session('auth_role') === 'master' ? 'M' : 'S' }}
                        </div>
                    </div>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-2 z-[1] p-2 shadow-xl bg-white rounded-2xl w-44 border border-base-200">
                        <li class="menu-title text-xs px-2 pb-1">
                            {{ session('auth_role') === 'master' ? 'Master Admin' : 'Sukarelawan' }}
                        </li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="text-error font-medium w-full text-left flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Log Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="flex-1 p-4 md:p-6 animate-fade-in">
            {{-- Session Alerts --}}
            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium animate-slide-up">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm font-medium animate-slide-up">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="py-3 px-6 text-center text-xs text-base-content/30 border-t border-base-200 bg-white">
            © {{ date('Y') }} Microbit Robotic Innovation Competition — MRIC TMS v1.0
        </footer>
    </div>

    {{-- Sidebar --}}
    <aside class="drawer-side z-40">
        <label for="sidebar-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
        <nav class="w-64 min-h-full bg-white border-r border-base-200 flex flex-col">

            {{-- Logo Area --}}
            <div class="px-5 py-5 border-b border-base-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex gap-1.5">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/company-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                            <span class="text-primary text-lg hidden">??</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('images/competition-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                            <span class="text-secondary text-lg hidden">??</span>
                        </div>
                    </div>
                </div>
                <p class="font-extrabold text-sm text-primary leading-tight">MICROBIT ROBOTIC</p>
                <p class="font-bold text-xs text-secondary leading-tight">INNOVATION COMPETITION</p>
                <p class="text-xs text-base-content/40 mt-0.5">Sistem Pengurusan Pertandingan</p>
            </div>

            {{-- Navigation --}}
            <div class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

                @if(session('auth_role') === 'master')
                {{-- Master-only section --}}
                <p class="px-4 pt-2 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30">Utama</p>
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Papan Pemuka
                </a>

                <p class="px-4 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30">Data</p>
                <a href="{{ route('admin.import.index') }}"
                   class="nav-item {{ request()->routeIs('admin.import.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import CSV
                </a>
                <a href="{{ route('admin.teams.index') }}"
                   class="nav-item {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                    Senarai Pasukan
                </a>
                @endif

                <p class="px-4 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30">Hari Pertandingan</p>
                <a href="{{ route('admin.checkin.index') }}"
                   class="nav-item {{ request()->routeIs('admin.checkin.index') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Semak Masuk
                </a>
                <a href="{{ route('admin.checkin.dashboard') }}"
                   class="nav-item {{ request()->routeIs('admin.checkin.dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Papan Kehadiran
                </a>

                <p class="px-4 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30">Pertandingan</p>

                @if(session('auth_role') === 'master')
                <a href="{{ route('admin.groups.index') }}"
                   class="nav-item {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Jana Kumpulan
                </a>
                @endif

                <a href="{{ route('admin.matches.index') }}"
                   class="nav-item {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Perlawanan
                </a>
                <a href="{{ route('admin.knockout.index') }}"
                   class="nav-item {{ request()->routeIs('admin.knockout.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                    Bracket Knockout
                </a>
            </div>

            {{-- Bottom logout --}}
            <div class="px-3 py-3 border-t border-base-200">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="nav-item w-full text-error hover:bg-red-50 hover:text-red-600 justify-start">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Log Keluar
                    </button>
                </form>
            </div>
        </nav>
    </aside>
</div>

@livewireScripts
</body>
</html>
