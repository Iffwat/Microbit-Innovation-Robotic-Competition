@extends('layouts.admin')
@section('title', __('Kumpulan') . ' ' . strtoupper($category))
@section('page-title', __('Senarai Kumpulan') . ' - ' . strtoupper($category))

@section('content')
    <livewire:admin.groups.group-detail :gameType="$game" :categorySlug="$category" />
@endsection
