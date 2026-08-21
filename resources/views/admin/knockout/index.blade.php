@extends('layouts.admin')
@section('title', 'Bracket Knockout')
@section('page-title', '🏆 Bracket Knockout')
@section('content')
<div class="hero min-h-64 bg-base-200 rounded-2xl">
    <div class="hero-content text-center">
        <div>
            <div class="text-6xl mb-4">🏆</div>
            <h2 class="text-2xl font-bold mb-2">Modul Bracket Knockout</h2>
            <p class="text-base-content/60 mb-4">Modul ini akan dibina dalam Fasa 4.<br>Sila selesaikan peringkat kumpulan dahulu.</p>
            <a href="{{ route('admin.groups.index') }}" class="btn btn-primary">🔢 Pergi ke Kumpulan</a>
        </div>
    </div>
</div>
@endsection
