<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="mric">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semakan Pasukan � MIRC</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-base-200 min-h-screen flex flex-col">

    {{-- Public Navbar --}}
    <header class="bg-white border-b border-base-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-4">

            {{-- Brand --}}
            <div class="flex items-center gap-3">
                <div class="flex gap-1.5">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('images/company-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                        <span class="text-primary text-sm hidden">??</span>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-secondary/10 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('images/competition-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                        <span class="text-secondary text-sm hidden">??</span>
                    </div>
                </div>
                <div class="hidden sm:block">
                    <p class="font-extrabold text-sm text-primary leading-none">MICROBIT INNOVATION</p>
                    <p class="font-semibold text-xs text-secondary leading-none mt-0.5">ROBOTIC COMPETITION</p>
                </div>
            </div>

            {{-- Right side --}}
            <div class="flex items-center gap-3">
                {{-- Language Toggle --}}
                <div class="join border border-base-300 rounded-lg overflow-hidden">
                    <a href="{{ route('lang.switch', 'ms') }}"
                       class="join-item px-3 py-1.5 text-xs font-semibold transition-colors {{ app()->getLocale() === 'ms' ? 'bg-primary text-white' : 'bg-white text-base-content/60 hover:bg-base-200' }}">MS</a>
                    <a href="{{ route('lang.switch', 'en') }}"
                       class="join-item px-3 py-1.5 text-xs font-semibold transition-colors {{ app()->getLocale() === 'en' ? 'bg-primary text-white' : 'bg-white text-base-content/60 hover:bg-base-200' }}">EN</a>
                </div>
                <a href="{{ route('admin.login') }}"
                   class="btn btn-primary btn-sm rounded-lg gap-2 text-xs font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    Log Masuk
                </a>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 w-full max-w-5xl mx-auto p-4 py-8 animate-fade-in">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-base-200 py-5 text-center text-xs text-base-content/40">
        <p class="font-medium">� {{ date('Y') }} Microbit Innovation Robotic Competition</p>
        <p class="mt-1">Sistem Pengurusan Pertandingan</p>
    </footer>

    @livewireScripts
</body>
</html>
