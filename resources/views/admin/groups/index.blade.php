@extends('layouts.admin')
@section('title', 'Jana Kumpulan')
@section('page-title', '🔢 Pengurusan Kumpulan')
@section('content')
<div class="hero min-h-64 bg-base-200 rounded-2xl">
    <div class="hero-content text-center">
        <div>
            <div class="text-6xl mb-4">🔢</div>
            <h2 class="text-2xl font-bold mb-2">Modul Kumpulan</h2>
            <p class="text-base-content/60 mb-4">Modul ini akan dibina dalam Fasa 3.<br>Sila selesaikan semak masuk dahulu.</p>
            <a href="{{ route('admin.checkin.index') }}" class="btn btn-primary">✅ Pergi ke Semak Masuk</a>
        </div>
    </div>
</div>
@endsection
