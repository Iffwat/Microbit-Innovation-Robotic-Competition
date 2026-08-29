@extends('layouts.admin')
@section('title', __('Carta Kalah Mati') . ': ' . $category->name)
@section('page-title', __('Carta Kalah Mati'))
@section('content')
    <livewire:admin.knockout-bracket :category="$category" />
@endsection
