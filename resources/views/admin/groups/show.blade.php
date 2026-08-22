@extends('layouts.admin')
@section('title', 'Kumpulan ' . strtoupper($category))
@section('page-title', 'Senarai Kumpulan - ' . strtoupper($category))

@section('content')
    <livewire:admin.groups.group-detail :gameType="$game" :categorySlug="$category" />
@endsection
