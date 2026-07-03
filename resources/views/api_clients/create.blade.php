@extends('api_clients.layout')

@section('title', 'Create API Token')

@section('content')
    <div class="topbar">
        <h1>Create API Token</h1>
        <a class="button secondary" href="{{ route('api_clients.index') }}">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="panel" method="post" action="{{ route('api_clients.store') }}">
        @csrf
        @include('api_clients.form', ['apiClient' => null])
    </form>
@endsection
