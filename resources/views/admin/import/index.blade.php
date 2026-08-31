@extends('layouts.admin')
@section('title', __('Import Data CSV'))
@section('page-title', '📤 ' . __('Import Data CSV'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Info Card --}}
    <div class="alert alert-info shadow">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <p class="font-semibold">{{ __('Arahan Import') }}</p>
            <ul class="text-sm mt-1 list-disc list-inside space-y-1">
                <li>{{ __('Export Google Sheets sebagai') }} <strong>CSV (.csv)</strong></li>
                <li>{{ __('Pastikan format kolum mengikut borang pendaftaran asal') }}</li>
                <li>{{ __('Pasukan duplikat (nama pasukan + kategori sama) akan dilangkau') }}</li>
                <li>{{ __('Saiz fail maksimum:') }} <strong>10MB</strong></li>
            </ul>
        </div>
    </div>

    {{-- Errors from last import --}}
    @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="alert alert-warning shadow">
        <div>
            <p class="font-semibold">⚠️ {{ __('Beberapa baris mempunyai ralat:') }}</p>
            <ul class="text-sm mt-2 list-disc list-inside">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Upload Form --}}
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-xl mb-4">{{ __('Muat Naik Fail CSV') }}</h2>

            <form action="{{ route('admin.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Game Type Selection --}}
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text font-medium">{{ __('Pilih Jenis Permainan') }}</span>
                    </label>
                    <select name="game_type" class="select select-bordered select-primary w-full" required>
                        <option value="isobot">{{ __('Isobot Soccer') }}</option>
                        <option value="sky_soccer">{{ __('Drone Sky Soccer') }}</option>
                        <option value="obstacle">{{ __('Drone Obstacle') }}</option>
                    </select>
                </div>

                {{-- File Input --}}
                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text font-medium">{{ __('Pilih fail CSV') }}</span>
                    </label>
                    <input type="file"
                           name="csv_file"
                           id="csv_file"
                           accept=".csv,.txt"
                           class="file-input file-input-bordered file-input-primary w-full" required />
                    @error('csv_file')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                {{-- Category Stats (current) --}}
                <div class="bg-base-200 rounded-xl p-4 mb-4">
                    <p class="text-sm font-semibold mb-3 text-base-content/70">{{ __('Status Semasa Pangkalan Data:') }}</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($categories as $cat)
                        <div class="text-center">
                            <div class="text-xl font-bold text-primary">{{ $cat->teams_count }}</div>
                            <div class="text-xs text-base-content/60">{{ $cat->name }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-actions justify-between items-center">
                    <a href="{{ route('admin.teams.index') }}" class="btn btn-ghost">
                        👁️ {{ __('Lihat Pasukan Sedia Ada') }}
                    </a>
                    <button type="submit" class="btn btn-primary gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        {{ __('Import Data') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Danger Zone --}}
    <div class="card border border-error bg-base-100 shadow-xl mt-8">
        <div class="card-body">
            <h3 class="card-title text-error"><i class="fas fa-exclamation-triangle"></i> {{ __('Zon Bahaya (Kosongkan Data)') }}</h3>
            <p class="text-sm opacity-80">{{ __('Padam rekod pasukan yang telah diimport berdasarkan jenis permainan.') }}</p>
            <form action="{{ route('admin.import.clear') }}" method="POST" 
                  onsubmit="return confirm('{{ __('AMARAN: Adakah anda pasti mahu memadam rekod pasukan bagi permainan ini? Tindakan ini tidak boleh dipulihkan.') }}');">
                @csrf
                <div class="flex flex-col sm:flex-row gap-2 mt-4 items-end">
                    <div class="form-control w-full sm:w-auto">
                        <label class="label"><span class="label-text">{{ __('Pilih Permainan untuk Dipadam') }}</span></label>
                        <select name="game_type" class="select select-bordered select-error" required>
                            <option value="" disabled selected>-- {{ __('Pilih Permainan') }} --</option>
                            <option value="isobot">{{ __('Isobot Soccer') }}</option>
                            <option value="sky_soccer">{{ __('Drone Sky Soccer') }}</option>
                            <option value="obstacle">{{ __('Drone Obstacle') }}</option>
                            <option value="all">⚠️ {{ __('PADAM SEMUA (Semua Permainan)') }}</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-error btn-outline mt-2 sm:mt-0 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        {{ __('Kosongkan Data') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Column Mapping Reference --}}
    <div class="collapse collapse-arrow bg-base-100 shadow mt-6">
        <input type="checkbox" />
        <div class="collapse-title font-semibold">
            📋 {{ __('Rujukan Susunan Kolum CSV (klik untuk buka)') }}
        </div>
        <div class="collapse-content">
            <div class="overflow-x-auto">
                <table class="table table-xs table-zebra">
                    <thead>
                        <tr>
                            <th>{{ __('Kolum #') }}</th>
                            <th>{{ __('Nama Kolum (Google Sheets)') }}</th>
                            <th>{{ __('Medan Sistem') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>1</td><td>{{ __('Sekolah') }}</td><td>{{ __('Nama Sekolah') }}</td></tr>
                        <tr><td>2</td><td>{{ __('Kategori') }}</td><td>U12 / U15 / U20 / PPKI</td></tr>
                        <tr><td>3</td><td>{{ __('Pasukan') }}</td><td>{{ __('Nama Pasukan') }}</td></tr>
                        <tr><td>4</td><td>{{ __('Pemain 1') }}</td><td>{{ __('Nama Pemain 1') }}</td></tr>
                        <tr><td>5</td><td>{{ __('Pemain 2') }}</td><td>{{ __('Nama Pemain 2') }}</td></tr>
                        <tr><td>6</td><td>{{ __('Pemain 3') }}</td><td>{{ __('Nama Pemain 3') }}</td></tr>
                        <tr><td>7</td><td>{{ __('Nama Guru') }}</td><td>{{ __('Nama Guru Pembimbing') }}</td></tr>
                        <tr><td>8</td><td>{{ __('E-mel Guru') }} <span class="badge badge-xs badge-neutral">{{ __('Opsional') }}</span></td><td>{{ __('Boleh dibiarkan kosong') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
