@extends('layouts.admin')
@section('title', 'Urus Carta Kalah Mati: ' . $category->name)
@section('page-title', 'Carta Kalah Mati')
@section('content')
    <livewire:admin.knockout-bracket :category="$category" />
@endsection
