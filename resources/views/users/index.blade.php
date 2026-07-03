@extends('api_clients.layout')

@section('title', 'Users')

@section('content')
    <div class="topbar">
        <div>
            <h1>Users</h1>
            <p>จัดการบัญชีผู้ใช้งานหลังบ้าน</p>
        </div>
        <a class="button" href="{{ route('users.create') }}">Add user</a>
    </div>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td data-label="Name"><a href="{{ route('users.show', $user) }}">{{ $user->fullName() }}</a></td>
                        <td data-label="Email">{{ $user->email }}</td>
                        <td data-label="Phone">{{ $user->phone_no ?: '-' }}</td>
                        <td data-label="Status"><span class="badge {{ $user->status }}">{{ $user->status }}</span></td>
                        <td data-label="Created">{{ $user->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td data-label="Action" class="actions">
                            <a href="{{ route('users.edit', $user) }}">Edit</a>
                            <form method="POST" action="{{ route('users.changeStatus', $user) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'inactive' : 'active' }}">
                                <button type="submit" class="button secondary">{{ $user->status === 'active' ? 'Disable' : 'Enable' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
