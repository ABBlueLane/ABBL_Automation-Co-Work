@extends('api_clients.layout')

@section('title', 'Edit API Client')

@section('content')
    <div class="topbar">
        <h1>Edit API Client</h1>
        <a class="button secondary" href="{{ route('api_clients.index') }}">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="panel" method="post" action="{{ route('api_clients.update', $apiClient) }}">
        @csrf
        @method('PUT')
        @include('api_clients.form', ['apiClient' => $apiClient])
    </form>
@endsection
