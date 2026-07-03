@extends('api_clients.layout')

@section('title', 'Add User')

@section('content')
    <div class="topbar">
        <div>
            <h1>Add User</h1>
            <p>สร้างบัญชีผู้ใช้งานหลังบ้าน</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('users.store') }}">
            @include('users.form')
        </form>
    </div>
@endsection
