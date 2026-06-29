@extends('adminlte::page')

@section('title', config('adminlte.title', 'Iron Gym'))

@section('css')
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/backoffice.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iron-gym-brand.css') }}">
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $page_title ?? 'Dashboard' }}</h1>
        @livewire('backoffice.shared.notification-bell')
    </div>
@stop

@section('content')
    <a href="#main-content" class="skip-link">Salta al contenuto</a>
    <div id="main-content">
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

    @if(config('features.in_app_feedback_enabled'))
        @livewire('shared.in-app-feedback')
    @endif
    </div>
@stop

@section('plugins.Livewire', true)
