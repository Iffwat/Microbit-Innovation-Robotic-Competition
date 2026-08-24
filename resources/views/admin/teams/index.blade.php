@extends('layouts.admin')
@section('title', 'Senarai Pasukan')
@section('page-title', '👥 Senarai Pasukan')

@section('content')
<div class="space-y-5 animate-slide-up">

    @if(session('success'))
        <div class="alert alert-success rounded-2xl shadow-sm text-sm font-semibold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Filters & Search --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 md:p-5">
            <form method="GET" action="{{ route('admin.teams.index') }}" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari nama pasukan, sekolah..."
                           class="input input-bordered input-sm w-full pl-10 focus:input-primary rounded-xl" />
                </div>
                <select name="game_type" class="select select-bordered select-sm w-full md:w-44 rounded-xl">
                    <option value="">Semua Permainan</option>
                    <option value="isobot" {{ request('game_type') === 'isobot' ? 'selected' : '' }}>Isobot Soccer</option>
                    <option value="sky_soccer" {{ request('game_type') === 'sky_soccer' ? 'selected' : '' }}>Drone Sky Soccer</option>
                    <option value="obstacle" {{ request('game_type') === 'obstacle' ? 'selected' : '' }}>Drone Obstacle</option>
                </select>
                <select name="category" class="select select-bordered select-sm w-full md:w-44 rounded-xl">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="select select-bordered select-sm w-full md:w-36 rounded-xl">
                    <option value="">Semua Status</option>
                    <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>Berdaftar</option>
                    <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Hadir</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Tidak Hadir</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-xl px-4">🔍 Cari</button>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-ghost btn-sm rounded-xl">Reset</a>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 md:px-6 border-b border-base-200">
                <div>
                    <h2 class="font-extrabold text-base-content text-base">Senarai Pasukan Berdaftar</h2>
                    <p class="text-xs text-base-content/50">Jumlah: <span class="font-bold text-primary">{{ $teams->total() }}</span> pasukan</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    @if($teams->total() > 0)
                    <form method="POST" action="{{ route('admin.teams.destroy_all') }}" onsubmit="return confirm('AMARAN MUTLAK: Anda pasti mahu MEMADAM SEMUA PASUKAN, KUMPULAN & PERLAWANAN? Tindakan ini tidak boleh diundur!');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-error btn-sm rounded-xl gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Padam Semua
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('admin.teams.create') }}" class="btn btn-primary btn-sm rounded-xl gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Pasukan
                    </a>
                    <a href="{{ route('admin.import.index') }}" class="btn btn-outline btn-sm rounded-xl gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import CSV
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm w-full">
                    <thead>
                        <tr class="bg-base-200/50 text-xs text-base-content/60">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Permainan & Kategori</th>
                            <th class="py-3 px-4">Pasukan & Sekolah</th>
                            <th class="py-3 px-4">Senarai Pemain</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Masa Hadir</th>
                            <th class="py-3 px-4 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200">
                        @forelse($teams as $index => $team)
                        <tr class="hover:bg-base-200/40 transition-colors">
                            <td class="text-base-content/40 text-xs px-4">{{ $teams->firstItem() + $index }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $gameColors = [
                                        'isobot'    => 'bg-blue-100 text-blue-700',
                                        'sky_soccer'=> 'bg-violet-100 text-violet-700',
                                        'obstacle'  => 'bg-amber-100 text-amber-700',
                                    ];
                                    $gameColor = $gameColors[$team->game_type] ?? 'bg-base-200 text-base-content/60';
                                @endphp
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full mb-1 block w-fit {{ $gameColor }}">
                                    {{ $team->game_type_label }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold bg-base-200 text-base-content/70 px-2 py-0.5 rounded-full w-fit">
                                    {{ $team->category->name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-sm text-base-content">{{ $team->team_name }}</div>
                                <div class="text-xs text-base-content/50 font-medium">🏫 {{ $team->school_name }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div class="space-y-0.5">
                                    @if($team->player_1) <div><span class="font-bold text-primary">1.</span> {{ $team->player_1 }}</div> @endif
                                    @if($team->player_2) <div><span class="font-bold text-primary">2.</span> {{ $team->player_2 }}</div> @endif
                                    @if($team->player_3) <div><span class="font-bold text-primary">3.</span> {{ $team->player_3 }} <span class="text-base-content/40 text-[10px]">(Rizab)</span></div> @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($team->status === 'checked_in')
                                    <span class="status-badge-present">● Hadir</span>
                                @elseif($team->status === 'absent')
                                    <span class="status-badge-absent">● Tidak Hadir</span>
                                @else
                                    <span class="status-badge-pending">● Berdaftar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-center text-base-content/60">
                                {{ $team->checked_in_at ? $team->checked_in_at->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.teams.edit', $team) }}" 
                                       title="Kemaskini Pasukan & Pemain"
                                       class="btn btn-sm btn-ghost text-primary hover:bg-primary/10 rounded-xl px-2.5">
                                        ✏️ Edit
                                    </a>
                                    <a href="{{ route('admin.teams.show', $team) }}" 
                                       title="Lihat Butiran"
                                       class="btn btn-sm btn-ghost text-base-content/60 hover:bg-base-200 rounded-xl px-2">
                                        👁️
                                    </a>
                                    <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" 
                                          onsubmit="return confirm('Anda pasti mahu memadam pasukan {{ $team->team_name }}? Tindakan ini tidak boleh diundur.')"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Padam Pasukan" class="btn btn-sm btn-ghost text-error hover:bg-red-50 rounded-xl px-2">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-16 text-base-content/40">
                                <p class="font-semibold text-sm">Tiada pasukan dijumpai</p>
                                <p class="text-xs mt-1">Cuba ubah kata kunci carian atau tetapan tapisan.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($teams->hasPages())
                <div class="p-4 border-t border-base-200 flex justify-center">
                    {{ $teams->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
