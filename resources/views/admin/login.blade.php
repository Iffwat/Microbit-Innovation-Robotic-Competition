<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="MIRC">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk � MIRC TMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-primary via-[#1e3a8a] to-secondary flex items-center justify-center p-4">

    {{-- Background decorative elements --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 -right-20 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-accent/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/4 w-64 h-64 bg-secondary/10 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-sm relative animate-slide-up">

        {{-- Logo Header --}}
        <div class="text-center mb-8">
            <div class="flex justify-center gap-3 mb-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30 overflow-hidden">
                    <img src="{{ asset('images/company-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                    <span class="text-white text-2xl hidden">??</span>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30 overflow-hidden">
                    <img src="{{ asset('images/competition-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                    <span class="text-white text-2xl hidden">??</span>
                </div>
            </div>
            <h1 class="text-white font-extrabold text-xl tracking-wide">MICROBIT INNOVATION</h1>
            <p class="text-white/70 text-sm mt-0.5">ROBOTIC COMPETITION</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-base-content">Akses Admin</h2>
                <p class="text-sm text-base-content/50 mt-1">Masukkan Kod PIN untuk meneruskan</p>
            </div>

            @if(session('error'))
                <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-medium animate-fade-in">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-medium animate-fade-in">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('admin.login.post') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-base-content/60 uppercase tracking-widest mb-2">Kod PIN</label>
                    <input type="password"
                           name="pin"
                           placeholder="� � � �"
                           class="w-full text-center text-3xl tracking-[0.5em] font-bold border-2 border-base-300 rounded-2xl py-4 px-4 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all placeholder:tracking-widest placeholder:text-base-content/20"
                           maxlength="4"
                           pattern="[0-9]*"
                           inputmode="numeric"
                           autofocus
                           required />
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white font-bold py-3.5 rounded-2xl hover:opacity-90 active:scale-[0.99] transition-all shadow-lg shadow-primary/30 text-sm tracking-wide">
                    MASUK ?
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-base-200 text-center">
                <a href="{{ route('semakan') }}" class="text-xs text-base-content/40 hover:text-primary transition-colors">
                    ? Kembali ke Portal Awam
                </a>
            </div>
        </div>
    </div>
</body>
</html>
