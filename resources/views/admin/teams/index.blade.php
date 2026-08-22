@extends('layouts.admin')
@section('title', 'Senarai Pasukan')
@section('page-title', '👥 Senarai Pasukan')

@section('content')
<div class="space-y-4">

    {{-- Filters --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body py-4">
            <form method="GET" action="{{ route('admin.teams.index') }}" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari nama pasukan, sekolah..."
                           class="input input-bordered input-sm w-full" />
                </div>
                <select name="game_type" class="select select-bordered select-sm w-full md:w-40">
                    <option value="">Semua Permainan</option>
                    <option value="isobot" {{ request('game_type') === 'isobot' ? 'selected' : '' }}>Isobot Soccer</option>
                    <option value="sky_soccer" {{ request('game_type') === 'sky_soccer' ? 'selected' : '' }}>Drone Sky Soccer</option>
                    <option value="obstacle" {{ request('game_type') === 'obstacle' ? 'selected' : '' }}>Drone Obstacle</option>
                </select>
                <select name="category" class="select select-bordered select-sm w-full md:w-40">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="select select-bordered select-sm w-full md:w-40">
                    <option value="">Semua Status</option>
                    <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>Berdaftar</option>
                    <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Hadir</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Tidak Hadir</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">🔍 Cari</button>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body p-0">
            <div class="flex justify-between items-center p-4 border-b border-base-200">
                <h2 class="font-bold">Jumlah: <span class="text-primary">{{ $teams->total() }}</span> pasukan</h2>
                <a href="{{ route('admin.import.index') }}" class="btn btn-primary btn-sm gap-2">
                    📤 Import CSV
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Permainan & Kategori</th>
                            <th>Pasukan & Sekolah</th>
                            <th>Pemain</th>
                            <th>Status</th>
                            <th>Masa Semak Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teams as $index => $team)
                        <tr>
                            <td class="text-base-content/50 text-xs">{{ $teams->firstItem() + $index }}</td>
                            <td class="px-3 py-4">
                            @php
                                $gameColors = [
                                    'isobot'    => 'bg-blue-100 text-blue-700',
                                    'sky_soccer'=> 'bg-violet-100 text-violet-700',
                                    'obstacle'  => 'bg-amber-100 text-amber-700',
                                ];
                                $gameColor = $gameColors[$team->game_type] ?? 'bg-base-200 text-base-content/60';
                            @endphp
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full mb-1 block w-fit {{ $gameColor }}">{{ $team->game_type_label }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-medium bg-base-200 text-base-content/60 px-2 py-0.5 rounded-full w-fit">{{ $team->category->name }}</span>
                        </td>
                            <td>
                                <div class="font-semibold">{{ $team->team_name }}</div>
                                <div class="text-xs text-base-content/60">{{ $team->school_name }}</div>
                            </td>
                            <td class="text-xs">
                                @if($team->player_1) <div>1: {{ $team->player_1 }}</div> @endif
                                @if($team->player_2) <div>2: {{ $team->player_2 }}</div> @endif
                                @if($team->player_3) <div>3: {{ $team->player_3 }}</div> @endif
                            </td>
                            <td>
                                <span class="badge badge-sm
                                    {{ $team->status === 'checked_in' ? 'badge-success' : ($team->status === 'absent' ? 'badge-error' : 'badge-warning') }}">
                                    {{ $team->status_label }}
                                </span>
                            </td>
                            <td class="text-xs text-base-content/50">
                                {{ $team->checked_in_at ? $team->checked_in_at->format('h:i A') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-base-content/50">
                                <div class="text-5xl mb-3">📭</div>
                                <p>Tiada pasukan dijumpai.</p>
                                @if(!request()->hasAny(['search', 'category', 'status']))
                                    <a href="{{ route('admin.import.index') }}" class="btn btn-primary btn-sm mt-3">
                                        Import Data CSV
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($teams->hasPages())
            <div class="p-4 border-t border-base-200">
                {{ $teams->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
