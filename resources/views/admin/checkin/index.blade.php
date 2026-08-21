@extends('layouts.admin')
@section('title', 'Semak Masuk Pasukan')
@section('page-title', '✅ Semak Masuk Pasukan')

@section('content')
<div class="space-y-4">
    @if(session('attendance_locked'))
    <div class="alert alert-error shadow">
        🔒 <strong>Kehadiran telah dikunci.</strong> Untuk buka kunci, pergi ke
        <a href="{{ route('admin.checkin.dashboard') }}" class="link link-warning">Papan Pemuka Kehadiran</a>.
    </div>
    @endif

    <livewire:check-in />
</div>
@endsection
