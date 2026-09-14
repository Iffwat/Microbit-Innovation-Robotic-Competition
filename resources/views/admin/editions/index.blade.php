<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="mric">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pusat Pengurusan Edisi Kejohanan Microbit Innovation Robotic Competition">
    <title>{{ __('Pilih Edisi Kejohanan') }} • MIRC TMS</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f172a] text-slate-100 min-h-screen flex flex-col selection:bg-primary selection:text-white" style="font-family: 'Inter', sans-serif;">

    {{-- Background Glows --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-secondary/15 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl"></div>
    </div>

    {{-- Top Navbar --}}
    <header class="relative z-10 border-b border-white/10 bg-slate-900/60 backdrop-blur-md px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex gap-1.5">
                    <div class="w-9 h-9 rounded-xl bg-primary/20 border border-primary/30 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('images/company-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                        <span class="text-primary text-base hidden">⚡</span>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-secondary/20 border border-secondary/30 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('images/competition-logo.png') }}" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                        <span class="text-secondary text-base hidden">🤖</span>
                    </div>
                </div>
                <div>
                    <h1 class="text-sm font-black tracking-wider text-white">MICROBIT INNOVATION ROBOTIC COMPETITION</h1>
                    <p class="text-[11px] text-white/50">Sistem Pengurusan Kejohanan &bull; Portal Pentadbir</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Role Badge --}}
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-xs">
                    <span class="w-2 h-2 rounded-full {{ session('auth_role') === 'master' ? 'bg-primary' : 'bg-secondary' }} animate-pulse"></span>
                    <span class="font-bold text-white/80">{{ session('auth_role') === 'master' ? 'Master Admin' : 'Sukarelawan' }}</span>
                </div>

                {{-- Public Portal Link --}}
                <a href="{{ route('semakan') }}" target="_blank"
                   class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-white/70 hover:text-white transition-all">
                    <span>Lihat Portal Awam</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                {{-- Logout Button --}}
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-xs font-bold text-red-400 hover:text-red-300 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Log Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-6 py-10 md:py-14 flex flex-col justify-center animate-slide-up">

        {{-- Alerts --}}
        @if(session('success'))
            <div class="mb-8 flex items-center gap-3 bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 rounded-2xl px-5 py-3.5 text-sm font-medium">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('info'))
            <div class="mb-8 flex items-center gap-3 bg-blue-500/15 border border-blue-500/30 text-blue-300 rounded-2xl px-5 py-3.5 text-sm font-medium">
                <svg class="w-5 h-5 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        {{-- Header Title --}}
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-bold text-primary mb-4">
                <span>🏆 PUSAT PENGURUSAN EDISI</span>
            </div>
            <h2 class="text-3xl md:text-5xl font-black tracking-tight text-white mb-3">
                Pilih Edisi Kejohanan mIRC
            </h2>
            <p class="text-base md:text-lg text-slate-400 leading-relaxed">
                Pilih tahun atau edisi kejohanan yang ingin anda uruskan. Anda boleh mengurus rekod semasa atau memulakan persediaan bagi edisi seterusnya.
            </p>
        </div>

        {{-- Grid of Editions --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            @foreach($editions as $item)
                @php
                    $is2026 = $item['year'] === '2026';
                    $is2027 = $item['year'] === '2027';
                    $is2025 = $item['year'] === '2025';
                @endphp

                <div class="group relative rounded-3xl border transition-all duration-300 flex flex-col justify-between overflow-hidden
                    {{ $is2026 ? 'bg-gradient-to-b from-slate-800/90 to-slate-900/90 border-emerald-500/40 hover:border-emerald-400 hover:shadow-2xl hover:shadow-emerald-500/10 hover:-translate-y-1' : '' }}
                    {{ $is2027 ? 'bg-gradient-to-b from-slate-800/60 to-slate-900/60 border-purple-500/30 hover:border-purple-400 hover:shadow-2xl hover:shadow-purple-500/10 hover:-translate-y-1' : '' }}
                    {{ $is2025 ? 'bg-gradient-to-b from-slate-900/40 to-slate-950/40 border-white/10 hover:border-white/20 opacity-80 hover:opacity-100 hover:-translate-y-0.5' : '' }}">

                    {{-- Top Accent Line --}}
                    @if($is2026)
                        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-teal-400 to-primary"></div>
                    @elseif($is2027)
                        <div class="h-1.5 w-full bg-gradient-to-r from-purple-500 via-indigo-400 to-pink-500"></div>
                    @else
                        <div class="h-1.5 w-full bg-slate-700"></div>
                    @endif

                    <div class="p-6 md:p-8 flex-1 flex flex-col justify-between">
                        <div>
                            {{-- Badge & Edition Label --}}
                            <div class="flex items-center justify-between gap-2 mb-4">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">
                                    {{ $item['edition'] }}
                                </span>
                                @if($is2026)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        {{ $item['status_label'] }}
                                    </span>
                                @elseif($is2027)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-500/15 text-purple-400 border border-purple-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                                        {{ $item['status_label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/5 text-slate-400 border border-white/10">
                                        {{ $item['status_label'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Year & Title --}}
                            <div class="mb-3">
                                <h3 class="text-2xl md:text-3xl font-black text-white group-hover:text-primary transition-colors flex items-center gap-2">
                                    {{ $item['title'] }}
                                    @if($is2026)
                                        <span class="text-base" title="Edisi dengan data aktif">⭐</span>
                                    @endif
                                </h3>
                                <p class="text-xs font-medium text-slate-400 mt-1 flex items-center gap-2">
                                    <span>📅 {{ $item['date'] }}</span>
                                    <span>&bull;</span>
                                    <span>📍 {{ $item['location'] }}</span>
                                </p>
                            </div>

                            {{-- Description --}}
                            <p class="text-sm text-slate-300 leading-relaxed mb-6">
                                {{ $item['description'] }}
                            </p>

                            {{-- Quick Stats Box --}}
                            @if($item['stats'])
                                <div class="grid grid-cols-3 gap-2.5 p-3 rounded-2xl bg-black/30 border border-white/5 mb-6">
                                    <div class="text-center">
                                        <p class="text-lg md:text-xl font-black text-white">{{ $item['stats']['teams_count'] }}</p>
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase">Pasukan</p>
                                    </div>
                                    <div class="text-center border-x border-white/10">
                                        <p class="text-lg md:text-xl font-black text-white">{{ $item['stats']['categories_count'] }}</p>
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase">Kategori</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-lg md:text-xl font-black text-white">{{ $item['stats']['matches_count'] }}</p>
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase">Perlawanan</p>
                                    </div>
                                </div>
                            @elseif($is2025)
                                <div class="p-3 rounded-2xl bg-black/20 border border-white/5 mb-6 text-center text-xs text-slate-400">
                                    Rekod kejohanan disimpan di arkib luar talian (offline storage).
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-2">
                            @if($is2026)
                                <form method="POST" action="{{ route('admin.editions.select') }}">
                                    @csrf
                                    <input type="hidden" name="year" value="2026">
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-primary hover:from-emerald-600 hover:to-primary/90 text-white font-black text-sm px-6 py-3.5 rounded-2xl shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-all">
                                        <span>Urus Kejohanan 2026</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </form>
                            @elseif($is2027)
                                <a href="{{ route('admin.editions.prepare', ['year' => '2027']) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 hover:text-white border border-purple-500/40 font-bold text-sm px-6 py-3.5 rounded-2xl transition-all">
                                    <span>Buka Mod Persediaan 2027</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                            @else
                                <div class="w-full text-center py-3 px-4 rounded-2xl bg-white/5 border border-white/10 text-xs font-semibold text-slate-400">
                                    Rekod Arkib Luar Talian
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

        </div>

        {{-- Quick Guidance Banner --}}
        <div class="mt-12 rounded-2xl bg-white/5 border border-white/10 p-5 md:p-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/20 border border-primary/30 flex items-center justify-center text-primary shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h4 class="font-bold text-white text-sm">Nota Pentadbir Kejohanan</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Pangkalan data aktif saat ini mengandungi data penuh **mIRC 2026**. Sebarang perlawanan, kehadiran, dan kedudukan kumpulan kekal selamat dan tersimpan rapi.</p>
                </div>
            </div>
            <a href="{{ route('semakan') }}" target="_blank" class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-xs font-bold text-white transition-all">
                <span>Papan Keputusan Awam</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>

    </main>

    {{-- Footer --}}
    <footer class="relative z-10 border-t border-white/10 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Microbit Innovation Robotic Competition &bull; Sistem Pengurusan Kejohanan (TMS)
    </footer>

</body>
</html>
