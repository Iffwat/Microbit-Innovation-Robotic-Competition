@extends('layouts.admin')
@section('title', __('Maklumat Pasukan'))
@section('page-title', __('Maklumat Pasukan'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.teams.index') }}" class="btn btn-ghost btn-sm">⬅️ {{ __('Kembali') }}</a>
        <h1 class="text-2xl font-bold">{{ $team->team_name }}</h1>
        <span class="badge badge-primary">{{ $team->category->name }}</span>
        <span class="badge {{ $team->status === 'checked_in' ? 'badge-success' : ($team->status === 'absent' ? 'badge-error' : 'badge-warning') }}">
            {{ $team->status_label }}
        </span>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title mb-4">{{ __('Profil Pasukan') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-base-content/60">{{ __('Nama Sekolah') }}</p>
                    <p class="font-semibold text-lg">{{ $team->school_name ?: '-' }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-base-content/60">{{ __('Masa Semak Masuk') }}</p>
                    <p class="font-semibold">{{ $team->checked_in_at ? $team->checked_in_at->format('d/m/Y h:i A') : __('Belum semak masuk') }}</p>
                </div>
            </div>

            <div class="divider">{{ __('Pemain') }}</div>

            <ul class="space-y-3">
                @if($team->player_1)
                <li class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content rounded-full w-10">
                            <span>1</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-bold">{{ $team->player_1 }}</p>
                        <p class="text-xs text-base-content/60">{{ __('Pemain Utama') }}</p>
                    </div>
                </li>
                @endif
                
                @if($team->player_2)
                <li class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content rounded-full w-10">
                            <span>2</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-bold">{{ $team->player_2 }}</p>
                        <p class="text-xs text-base-content/60">{{ __('Pemain Utama') }}</p>
                    </div>
                </li>
                @endif
                
                @if($team->player_3)
                <li class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content rounded-full w-10">
                            <span>3</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-bold">{{ $team->player_3 }}</p>
                        <p class="text-xs text-base-content/60">{{ __('Pemain Rizab') }}</p>
                    </div>
                </li>
                @endif
                
                @if(!$team->player_1 && !$team->player_2 && !$team->player_3)
                    <p class="text-base-content/50 italic">{{ __('Tiada pemain didaftarkan.') }}</p>
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection
