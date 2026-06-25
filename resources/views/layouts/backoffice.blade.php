@extends('adminlte::page')

@section('title', config('adminlte.title', 'Iron Gym'))

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $page_title ?? 'Dashboard' }}</h1>
        @livewire('backoffice.shared.notification-bell')
    </div>
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

    @if(config('features.in_app_feedback_enabled'))
        @livewire('shared.in-app-feedback')
    @endif
@stop

@section('plugins.Livewire', true)
