@extends('adminlte::page')

@section('title', config('adminlte.title', 'Iron Gym'))

@section('content_header')
    <h1>{{ $page_title ?? 'Dashboard' }}</h1>
@stop

@section('content')
    @if (session('success'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            x-transition
            class="alert alert-success alert-dismissible"
        >
            <button type="button" class="close" @click="show = false"><span>&times;</span></button>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            x-transition
            class="alert alert-danger alert-dismissible"
        >
            <button type="button" class="close" @click="show = false"><span>&times;</span></button>
            {{ session('error') }}
        </div>
    @endif

    {{ $slot }}
@stop

@section('plugins.Livewire', true)
