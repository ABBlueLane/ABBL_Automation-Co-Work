@extends('api_clients.layout')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-stat-card {
            background: var(--vz-card-bg);
            border: 1px solid var(--vz-border-color);
            border-radius: .25rem;
            box-shadow: var(--vz-box-shadow-sm);
            padding: 1.25rem;
        }

        .dashboard-stat-card .stat-label {
            color: var(--vz-secondary-color);
            font-size: .8125rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: .5rem;
        }

        .dashboard-stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: .25rem;
        }

        .dashboard-stat-card .stat-sub {
            color: var(--vz-secondary-color);
            font-size: .875rem;
        }

        .dashboard-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }

        .dashboard-link-card {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            background: var(--vz-card-bg);
            border: 1px solid var(--vz-border-color);
            border-radius: .25rem;
            box-shadow: var(--vz-box-shadow-sm);
            padding: 1.1rem 1.25rem;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .dashboard-link-card:hover {
            border-color: var(--vz-primary);
            box-shadow: var(--vz-box-shadow);
            color: inherit;
        }

        .dashboard-link-card i {
            font-size: 1.5rem;
            color: var(--vz-primary);
            line-height: 1;
            margin-top: .15rem;
        }

        .dashboard-link-card h3 {
            margin: 0 0 .35rem;
            font-size: 1rem;
            font-weight: 600;
        }

        .dashboard-link-card p {
            margin: 0;
            color: var(--vz-secondary-color);
            font-size: .875rem;
        }
    </style>
@endpush

@section('content')
    <div class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>ยินดีต้อนรับ{{ Auth::user()->fullName() ? ', '.Auth::user()->fullName() : '' }}</p>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="dashboard-stat-card">
            <div class="stat-label">ผู้ใช้งาน</div>
            <div class="stat-value">{{ number_format($stats['users_total']) }}</div>
            <div class="stat-sub">Active {{ number_format($stats['users_active']) }} คน</div>
        </div>
        <div class="dashboard-stat-card">
            <div class="stat-label">API Clients</div>
            <div class="stat-value">{{ number_format($stats['api_clients_active']) }}</div>
            <div class="stat-sub">Token ที่เปิดใช้งาน</div>
        </div>
        <div class="dashboard-stat-card">
            <div class="stat-label">ธุรกิจ</div>
            <div class="stat-value">{{ number_format($stats['businesses_total']) }}</div>
            <div class="stat-sub">Business ทั้งหมด</div>
        </div>
        <div class="dashboard-stat-card">
            <div class="stat-label">Issue เปิดอยู่</div>
            <div class="stat-value">{{ number_format($stats['issues_open']) }}</div>
            <div class="stat-sub">ยังไม่ปิดงาน</div>
        </div>
    </div>

    <div class="panel">
        <h2 style="margin: 0 0 1rem; font-size: 1.05rem; font-weight: 600;">เมนูลัด</h2>
        <div class="dashboard-links">
            <a href="{{ route('users.index') }}" class="dashboard-link-card">
                <i class="ri-user-settings-line"></i>
                <div>
                    <h3>Users</h3>
                    <p>จัดการบัญชีผู้ใช้งานหลังบ้าน</p>
                </div>
            </a>
            <a href="{{ route('api_clients.index') }}" class="dashboard-link-card">
                <i class="ri-key-2-line"></i>
                <div>
                    <h3>API Clients</h3>
                    <p>สร้างและจัดการ Bearer token</p>
                </div>
            </a>
            <a href="{{ route('logs.index') }}" class="dashboard-link-card">
                <i class="ri-file-list-3-line"></i>
                <div>
                    <h3>Logs</h3>
                    <p>ดูประวัติการใช้งานระบบ</p>
                </div>
            </a>
            <a href="{{ route('business.select') }}" class="dashboard-link-card">
                <i class="ri-bug-line"></i>
                <div>
                    <h3>Issue Management</h3>
                    <p>จัดการ Issue ตามธุรกิจ</p>
                </div>
            </a>
        </div>
    </div>
@endsection
