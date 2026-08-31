@extends('layouts.admin')
@section('title', __('Semak Masuk Pasukan'))
@section('page-title', __('Semak Masuk Pasukan'))

@section('content')
<div class="space-y-5">
    @if(session('attendance_locked'))
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl px-5 py-4">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <div>
            <p class="font-bold text-red-700 text-sm">{{ __('Kehadiran Telah Dikunci') }}</p>
            <p class="text-xs text-red-600 mt-0.5">{{ __('Tiada semak masuk baru boleh dilakukan.') }} <a href="{{ route('admin.checkin.dashboard') }}" class="underline font-bold hover:text-red-800">{{ __('Pergi ke Papan Pemuka untuk buka kunci.') }}</a></p>
        </div>
    </div>
    @endif

    <livewire:check-in />
</div>
@endsection
