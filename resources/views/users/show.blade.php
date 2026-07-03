@extends('api_clients.layout')

@section('title', 'User Detail')

@section('content')
    <div class="topbar">
        <div>
            <h1>{{ $user->fullName() }}</h1>
            <p>{{ $user->email }}</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('users.index') }}">Back</a>
            <a class="button" href="{{ route('users.edit', $user) }}">Edit</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <table>
            <tbody>
                <tr>
                    <th>Name</th>
                    <td>{{ $user->fullName() }}</td>
                </tr>
                <tr>
                    <th>Nickname</th>
                    <td>{{ $user->nick_name ?: '-' }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $user->email }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $user->phone_no ?: '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="badge {{ $user->status }}">{{ $user->status }}</span></td>
                </tr>
                <tr>
                    <th>Created</th>
                    <td>{{ $user->created_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
            </tbody>
        </table>

        <form class="mt-4" method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="button secondary">Delete user</button>
        </form>
    </div>
@endsection
