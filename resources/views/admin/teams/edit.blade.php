@extends('layouts.admin')
@section('title', __('Kemaskini Pasukan'))
@section('page-title', '✏️ ' . __('Kemaskini Pasukan'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6 animate-slide-up">
    
    {{-- Back & Header --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.teams.index') }}" class="btn btn-ghost btn-sm gap-2">
            ← {{ __('Kembali ke Senarai') }}
        </a>
        <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">ID: #{{ $team->id }}</span>
    </div>

    @if ($errors->any())
        <div class="alert alert-error rounded-2xl shadow">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>
                <h3 class="font-bold">{{ __('Sila periksa ralat berikut:') }}</h3>
                <ul class="list-disc list-inside text-sm mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card bg-base-100 shadow-xl border border-base-200">
        <form method="POST" action="{{ route('admin.teams.update', $team) }}" class="card-body p-6 md:p-8 space-y-6">
            @csrf
            @method('PUT')

            {{-- Section 1: Maklumat Pasukan & Sekolah --}}
            <div>
                <h3 class="text-base font-bold text-base-content flex items-center gap-2 mb-4 pb-2 border-b border-base-200">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                    {{ __('Maklumat Pasukan & Sekolah') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Nama Pasukan') }} <span class="text-error">*</span></span></label>
                        <input type="text" name="team_name" value="{{ old('team_name', $team->team_name) }}" required
                               class="input input-bordered w-full font-bold focus:input-primary" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Nama Sekolah') }} <span class="text-error">*</span></span></label>
                        <input type="text" name="school_name" value="{{ old('school_name', $team->school_name) }}" required
                               class="input input-bordered w-full focus:input-primary" />
                    </div>
                </div>
            </div>

            {{-- Section 2: Permainan & Kategori --}}
            <div>
                <h3 class="text-base font-bold text-base-content flex items-center gap-2 mb-4 pb-2 border-b border-base-200">
                    <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                    {{ __('Permainan, Kategori & Status') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Jenis Permainan') }} <span class="text-error">*</span></span></label>
                        <select name="game_type" required class="select select-bordered w-full focus:select-primary">
                            <option value="isobot" {{ old('game_type', $team->game_type) === 'isobot' ? 'selected' : '' }}>{{ __('Isobot Soccer') }}</option>
                            <option value="sky_soccer" {{ old('game_type', $team->game_type) === 'sky_soccer' ? 'selected' : '' }}>{{ __('Drone Sky Soccer') }}</option>
                            <option value="obstacle" {{ old('game_type', $team->game_type) === 'obstacle' ? 'selected' : '' }}>{{ __('Drone Obstacle') }}</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Kategori') }} <span class="text-error">*</span></span></label>
                        <select name="category_id" required class="select select-bordered w-full focus:select-primary">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string)old('category_id', $team->category_id) === (string)$cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Status Kehadiran') }} <span class="text-error">*</span></span></label>
                        <select name="status" required class="select select-bordered w-full focus:select-primary">
                            <option value="registered" {{ old('status', $team->status) === 'registered' ? 'selected' : '' }}>⏳ {{ __('Berdaftar (Belum Semak Masuk)') }}</option>
                            <option value="checked_in" {{ old('status', $team->status) === 'checked_in' ? 'selected' : '' }}>✅ {{ __('Hadir') }}</option>
                            <option value="absent" {{ old('status', $team->status) === 'absent' ? 'selected' : '' }}>❌ {{ __('Tidak Hadir') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Section 3: Senarai Pemain --}}
            <div>
                <h3 class="text-base font-bold text-base-content flex items-center gap-2 mb-4 pb-2 border-b border-base-200">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    {{ __('Senarai Nama Pemain (Boleh Ditukar)') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Pemain 1 (Kapten)') }}</span></label>
                        <input type="text" name="player_1" value="{{ old('player_1', $team->player_1) }}"
                               placeholder="{{ __('Nama penuh pemain 1') }}"
                               class="input input-bordered w-full focus:input-primary" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Pemain 2') }}</span></label>
                        <input type="text" name="player_2" value="{{ old('player_2', $team->player_2) }}"
                               placeholder="{{ __('Nama penuh pemain 2') }}"
                               class="input input-bordered w-full focus:input-primary" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Pemain 3 (Rizab / Simpanan)') }}</span></label>
                        <input type="text" name="player_3" value="{{ old('player_3', $team->player_3) }}"
                               placeholder="{{ __('Nama penuh pemain 3') }}"
                               class="input input-bordered w-full focus:input-primary" />
                    </div>
                </div>
            </div>

            {{-- Section 4: Guru Pengiring / Mentor --}}
            <div>
                <h3 class="text-base font-bold text-base-content flex items-center gap-2 mb-4 pb-2 border-b border-base-200">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    {{ __('Guru Pengiring / Mentor') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('Nama Guru / Mentor') }}</span></label>
                        <input type="text" name="mentor_name" value="{{ old('mentor_name', $team->mentor_name) }}"
                               placeholder="{{ __('Nama guru pembimbing') }}"
                               class="input input-bordered w-full focus:input-primary" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ __('No. Telefon / Emel') }}</span></label>
                        <input type="text" name="mentor_email" value="{{ old('mentor_email', $team->mentor_email) }}"
                               placeholder="{{ __('012-3456789 / emel') }}"
                               class="input input-bordered w-full focus:input-primary" />
                    </div>
                </div>
            </div>

            {{-- Submit buttons --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-base-200">
                <a href="{{ route('admin.teams.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                <button type="submit" class="btn btn-primary px-6 gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('Simpan Perubahan') }}
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
