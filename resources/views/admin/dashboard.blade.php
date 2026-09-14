@extends('layouts.admin')
@section('title', __('Papan Pemuka'))
@section('page-title', __('Papan Pemuka'))

@section('content')
<div class="space-y-6 animate-slide-up">

    {{-- Hero Banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary via-[#1e3a8a] to-secondary p-6 md:p-8 text-white">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 w-48 h-48 bg-accent/10 rounded-full blur-2xl"></div>
        </div>
        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
            <div>
                <p class="text-white/60 text-sm font-medium mb-1">{{ __('Selamat Datang, Master Admin') }}</p>
                <h1 class="text-2xl md:text-3xl font-extrabold mb-1 flex items-center flex-wrap gap-2">
                    <span>{{ __('Microbit Innovation Robotic') }}</span>
                    <span class="text-xs font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-3 py-1 rounded-full">
                        mIRC {{ session('active_edition', '2026') }}
                    </span>
                </h1>
                <p class="text-white/70 text-sm">{{ __('Sistem Pengurusan Pertandingan') }} • {{ now()->format('d F Y') }}</p>
                <div class="flex gap-2 mt-4 flex-wrap">
                    @if($totalTeams === 0)
                        <a href="{{ route('admin.import.index') }}" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold px-4 py-2 rounded-xl backdrop-blur-sm transition-colors border border-white/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            {{ __('Import Data Sekarang') }}
                        </a>
                    @else
                        <a href="{{ route('admin.checkin.index') }}" class="inline-flex items-center gap-2 bg-white text-primary text-sm font-bold px-4 py-2 rounded-xl shadow-lg hover:bg-white/90 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Semak Masuk') }}
                        </a>
                        <a href="{{ route('admin.groups.index') }}" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold px-4 py-2 rounded-xl backdrop-blur-sm transition-colors border border-white/20">
                            {{ __('Jana Kumpulan') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Game type filter --}}
            <div class="shrink-0">
                <form method="GET" action="{{ route('admin.dashboard') }}">
                    <select name="game_type" onchange="this.form.submit()"
                            class="bg-white/15 backdrop-blur-sm border border-white/25 text-white text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:bg-white/25 transition-all">
                        <option value="" class="text-base-content" {{ !$gameType ? 'selected' : '' }}>{{ __('Semua Permainan') }}</option>
                        <option value="isobot" class="text-base-content" {{ $gameType === 'isobot' ? 'selected' : '' }}>{{ __('Isobot Soccer') }}</option>
                        <option value="sky_soccer" class="text-base-content" {{ $gameType === 'sky_soccer' ? 'selected' : '' }}>{{ __('Drone Sky Soccer') }}</option>
                        <option value="obstacle" class="text-base-content" {{ $gameType === 'obstacle' ? 'selected' : '' }}>{{ __('Drone Obstacle') }}</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-base-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                </div>
                <span class="text-xs font-semibold text-base-content/40 bg-base-200 px-2 py-0.5 rounded-full">{{ __('Jumlah') }}</span>
            </div>
            <p class="text-3xl font-extrabold text-base-content">{{ $totalTeams }}</p>
            <p class="text-xs text-base-content/50 mt-1 font-medium">{{ __('Pasukan Didaftar') }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $totalTeams > 0 ? round(($totalCheckedIn / $totalTeams) * 100) : 0 }}%</span>
            </div>
            <p class="text-3xl font-extrabold text-emerald-600">{{ $totalCheckedIn }}</p>
            <p class="text-xs text-emerald-600/60 mt-1 font-medium">{{ __('Hadir') }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-red-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <span class="text-xs font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-full">{{ $totalTeams > 0 ? round(($totalAbsent / $totalTeams) * 100) : 0 }}%</span>
            </div>
            <p class="text-3xl font-extrabold text-red-500">{{ $totalAbsent }}</p>
            <p class="text-xs text-red-500/60 mt-1 font-medium">{{ __('Tidak Hadir') }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-bold text-amber-500 bg-amber-50 px-2 py-0.5 rounded-full">{{ $totalTeams > 0 ? round(($totalRegistered / $totalTeams) * 100) : 0 }}%</span>
            </div>
            <p class="text-3xl font-extrabold text-amber-500">{{ $totalRegistered }}</p>
            <p class="text-xs text-amber-500/60 mt-1 font-medium">{{ __('Belum Ditanda') }}</p>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- TOURNAMENT OPERATIONS & READINESS VISUAL STATUS MONITOR   --}}
    {{-- ========================================================= --}}
    <livewire:admin.tournament-status-monitor :gameType="$gameType" />

    {{-- Category Breakdown Table --}}
    <div class="bg-white rounded-2xl border border-base-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-base-200 flex items-center gap-3">
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h2 class="font-bold text-base-content">{{ __('Kehadiran Mengikut Kategori') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="text-left px-6 py-3 font-semibold text-base-content/50 text-xs uppercase tracking-wide">{{ __('Kategori') }}</th>
                        <th class="text-left px-3 py-3 font-semibold text-base-content/50 text-xs uppercase tracking-wide">{{ __('Format') }}</th>
                        <th class="text-center px-3 py-3 font-semibold text-base-content/50 text-xs uppercase tracking-wide">{{ __('Daftar') }}</th>
                        <th class="text-center px-3 py-3 font-semibold text-emerald-600 text-xs uppercase tracking-wide">{{ __('Hadir') }}</th>
                        <th class="text-center px-3 py-3 font-semibold text-red-500 text-xs uppercase tracking-wide">{{ __('Tidak') }}</th>
                        <th class="text-center px-3 py-3 font-semibold text-amber-500 text-xs uppercase tracking-wide">{{ __('Belum') }}</th>
                        <th class="px-6 py-3 font-semibold text-base-content/50 text-xs uppercase tracking-wide">{{ __('Kemajuan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($categories as $cat)
                    @php $pct = $cat->teams_count > 0 ? round(($cat->checked_in_count / $cat->teams_count) * 100) : 0; @endphp
                    <tr class="hover:bg-base-200/40 transition-colors">
                        <td class="px-6 py-4 font-semibold text-base-content">{{ $cat->name }}</td>
                        <td class="px-3 py-4">
                            <span class="text-xs font-medium bg-base-200 text-base-content/60 px-2 py-0.5 rounded-full">{{ $cat->format_label }}</span>
                        </td>
                        <td class="px-3 py-4 text-center font-bold">{{ $cat->teams_count }}</td>
                        <td class="px-3 py-4 text-center">
                            <span class="status-badge-present">{{ $cat->checked_in_count }}</span>
                        </td>
                        <td class="px-3 py-4 text-center">
                            <span class="status-badge-absent">{{ $cat->absent_count }}</span>
                        </td>
                        <td class="px-3 py-4 text-center">
                            <span class="status-badge-pending">{{ $cat->pending_count }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-base-200 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-base-content/60 w-8 text-right">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-16 text-base-content/40">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-base-200 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                </div>
                                <p class="font-medium">{{ __('Tiada data lagi') }}</p>
                                <a href="{{ route('admin.import.index') }}" class="text-primary text-sm font-semibold hover:underline">{{ __('Import CSV untuk bermula') }}</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div>
        <h2 class="text-sm font-bold text-base-content/50 uppercase tracking-widest mb-3">{{ __('Tindakan Pantas') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('admin.import.index') }}" class="group bg-white rounded-2xl p-5 border border-base-200 shadow-sm hover:border-primary hover:shadow-md transition-all flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                <span class="text-sm font-semibold text-base-content">{{ __('Import CSV') }}</span>
            </a>
            <a href="{{ route('admin.checkin.index') }}" class="group bg-white rounded-2xl p-5 border border-base-200 shadow-sm hover:border-emerald-400 hover:shadow-md transition-all flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-sm font-semibold text-base-content">{{ __('Semak Masuk') }}</span>
            </a>
            <a href="{{ route('admin.groups.index') }}" class="group bg-white rounded-2xl p-5 border border-base-200 shadow-sm hover:border-secondary hover:shadow-md transition-all flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 bg-secondary/10 rounded-xl flex items-center justify-center group-hover:bg-secondary/20 transition-colors">
                    <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <span class="text-sm font-semibold text-base-content">{{ __('Jana Kumpulan') }}</span>
            </a>
            <a href="{{ route('admin.knockout.index') }}" class="group bg-white rounded-2xl p-5 border border-base-200 shadow-sm hover:border-amber-400 hover:shadow-md transition-all flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                </div>
                <span class="text-sm font-semibold text-base-content">{{ __('Bracket Knockout') }}</span>
            </a>
        </div>
    </div>

</div>
@endsection
