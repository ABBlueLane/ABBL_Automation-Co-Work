@extends('api_clients.layout')

@section('title', 'API Clients')

@section('content')
    <div class="topbar">
        <div>
            <h1>API Clients</h1>
            <p>สร้างและจัดการ Bearer token สำหรับเรียกใช้งาน API</p>
        </div>
        <a class="button" href="{{ route('api_clients.create') }}">Create token</a>
    </div>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    @if (session('generated_token'))
        <div class="alert success">
            <strong>Generated token</strong>
            <p>Token นี้จะแสดงแค่ครั้งเดียว:</p>
            <div class="token">{{ session('generated_token') }}</div>
        </div>
    @endif

    <div class="filters">
        <a class="button {{ $status === 'active' ? '' : 'secondary' }}" href="{{ route('api_clients.index', ['status' => 'active']) }}">Active</a>
        <a class="button {{ $status === 'inactive' ? '' : 'secondary' }}" href="{{ route('api_clients.index', ['status' => 'inactive']) }}">Inactive</a>
        <a class="button {{ $status === 'all' ? '' : 'secondary' }}" href="{{ route('api_clients.index', ['status' => 'all']) }}">All</a>
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Last used</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td data-label="Version">{{ $client->version }}</td>
                        <td data-label="Description">{{ $client->description ?: '-' }}</td>
                        <td data-label="Status"><span class="badge {{ $client->status }}">{{ $client->status }}</span></td>
                        <td data-label="Last used">{{ $client->last_used_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                        <td data-label="Created">{{ $client->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td data-label="Action"><a href="{{ route('api_clients.edit', $client) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No API clients found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
