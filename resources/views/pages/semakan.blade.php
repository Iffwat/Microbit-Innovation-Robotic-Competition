@extends('layouts.public')

@section('content')
    @if(config('app.event_concluded', env('EVENT_CONCLUDED', true)))
        <livewire:keputusan-rasmi />
    @else
        <livewire:semakan />
    @endif
@endsection
