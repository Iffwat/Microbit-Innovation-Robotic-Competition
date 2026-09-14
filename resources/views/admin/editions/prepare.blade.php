<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="mric">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persediaan mIRC {{ $year }} • MIRC TMS</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f172a] text-slate-100 min-h-screen flex flex-col selection:bg-primary selection:text-white" style="font-family: 'Inter', sans-serif;">

    {{-- Top Navbar --}}
    <header class="relative z-10 border-b border-white/10 bg-slate-900/60 backdrop-blur-md px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.editions') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-xl border border-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Tukar Edisi</span>
                </a>
                <span class="text-white/20">|</span>
                <span class="text-sm font-black text-white">mIRC {{ $year }} &bull; Mod Persediaan</span>
            </div>

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('admin.editions.select') }}">
                    @csrf
                    <input type="hidden" name="year" value="2026">
                    <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/40 text-xs font-bold text-emerald-300 transition-all">
                        <span>Buka Data mIRC 2026</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Content --}}
    <main class="relative z-10 flex-1 max-w-5xl w-full mx-auto px-6 py-12 flex flex-col justify-center animate-slide-up">

        <div class="bg-gradient-to-b from-slate-800/90 to-slate-900/90 border border-purple-500/30 rounded-3xl p-8 md:p-12 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-80 h-80 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-500/15 border border-purple-500/30 text-xs font-extrabold text-purple-400 mb-6">
                <span>🚀 EDISI KE-4 DALAM PERANCANGAN</span>
            </div>

            <h2 class="text-3xl md:text-4xl font-black text-white mb-4">
                Penyediaan Edisi Kejohanan mIRC {{ $year }}
            </h2>

            <p class="text-slate-300 text-base leading-relaxed mb-8 max-w-3xl">
                Sistem pangkalan data aktif pelayan saat ini sedang mengekalkan rekod rasmi kejohanan **mIRC 2026** (154 Pasukan, keputusan kumpulan, dan bracket kalah mati).
                Untuk mengendalikan edisi baharu **mIRC {{ $year }}**, ikuti panduan persediaan di bawah:
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
                <div class="bg-black/30 border border-white/10 rounded-2xl p-5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-black mb-3">1</div>
                    <h4 class="font-bold text-white text-sm mb-1">Simpan Arkib 2026</h4>
                    <p class="text-xs text-slate-400">Pastikan pangkalan data mIRC 2026 dieksport dan dimuat turun melalui phpMyAdmin sebagai salinan kekal.</p>
                </div>

                <div class="bg-black/30 border border-white/10 rounded-2xl p-5">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-black mb-3">2</div>
                    <h4 class="font-bold text-white text-sm mb-1">Dapatkan Senarai 2027</h4>
                    <p class="text-xs text-slate-400">Sediakan fail CSV pendaftaran peserta mIRC {{ $year }} menggunakan format lajur yang telah diselaraskan.</p>
                </div>

                <div class="bg-black/30 border border-white/10 rounded-2xl p-5">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-black mb-3">3</div>
                    <h4 class="font-bold text-white text-sm mb-1">Import & Jana Undian</h4>
                    <p class="text-xs text-slate-400">Apabila musim {{ $year }} bermula, import senarai baharu ke sistem untuk memulakan undian kumpulan.</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-4 pt-4 border-t border-white/10">
                <form method="POST" action="{{ route('admin.editions.select') }}">
                    @csrf
                    <input type="hidden" name="year" value="2026">
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-sm px-6 py-3 rounded-xl shadow-lg transition-all">
                        <span>Buka Papan Pengurusan mIRC 2026</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

                <a href="{{ route('admin.editions') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 text-white font-semibold text-sm px-6 py-3 rounded-xl border border-white/10 transition-all">
                    <span>Kembali ke Pilihan Edisi</span>
                </a>
            </div>

        </div>

    </main>

    <footer class="relative z-10 border-t border-white/10 py-5 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Microbit Innovation Robotic Competition &bull; Sistem Pengurusan Kejohanan (TMS)
    </footer>

</body>
</html>
