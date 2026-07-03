@extends('api_clients.layout')

@section('title', 'Edit User')

@section('content')
    <div class="topbar">
        <div>
            <h1>Edit User</h1>
            <p>{{ $user->email }}</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @method('PUT')
            @include('users.form', ['user' => $user])
        </form>
    </div>
@endsection
