@extends('api_clients.layout')

@section('title', 'Cursor Autofix')

@section('content')
    <div class="topbar">
        <div>
            <h1>Cursor Autofix</h1>
            <p>ติดตามงานแก้ปัญหา IMS อัตโนมัติผ่าน Cursor CLI และสั่งรันใหม่ได้</p>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <a class="button secondary" href="{{ route('cursor_autofix.chat') }}">ทดสอบ CLI / แชท</a>
            <a class="button" href="{{ route('cursor_autofix.create') }}">สั่งรัน Autofix</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="panel" style="margin-bottom: 1rem;">
        <div style="display:flex; flex-wrap:wrap; gap:1rem; align-items:center;">
            <div>
                <strong>สถานะระบบ:</strong>
                @if ($enabled)
                    <span class="badge active">เปิดใช้งาน</span>
                @else
                    <span class="badge inactive">ปิดอยู่</span>
                @endif
            </div>
            <div>
                <strong>โหมด:</strong>
                {{ $dryRun ? 'Dry-run (ไม่เรียก CLI จริง)' : 'Live (เรียก Cursor CLI)' }}
            </div>
        </div>
    </div>

    <div class="filters">
        <a class="button {{ $status === 'all' ? '' : 'secondary' }}" href="{{ route('cursor_autofix.index', ['status' => 'all']) }}">ทั้งหมด</a>
        @foreach ($statusOptions as $key => $label)
            <a class="button {{ $status === $key ? '' : 'secondary' }}" href="{{ route('cursor_autofix.index', ['status' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Issue</th>
                    <th>Repo</th>
                    <th>สถานะ</th>
                    <th>Mode</th>
                    <th>สร้างเมื่อ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr>
                        <td data-label="ID">#{{ $run->id }}</td>
                        <td data-label="Issue">
                            @if ($run->issue)
                                <div><strong>{{ $run->issue->issue_number }}</strong></div>
                                <div style="color: var(--vz-secondary-color); font-size: .875rem;">{{ \Illuminate\Support\Str::limit($run->issue->title, 60) }}</div>
                            @else
                                Issue #{{ $run->issue_id }}
                            @endif
                        </td>
                        <td data-label="Repo">
                            {{ $run->repo_key ?: '-' }}
                            @if ($run->matched_host)
                                <div style="color: var(--vz-secondary-color); font-size: .8rem;">{{ $run->matched_host }}</div>
                            @endif
                        </td>
                        <td data-label="สถานะ">
                            <span class="badge {{ $run->status_badge_class }}">{{ $run->status_label }}</span>
                        </td>
                        <td data-label="Mode">{{ $run->dry_run ? 'dry-run' : 'live' }}</td>
                        <td data-label="สร้างเมื่อ">{{ $run->created_at?->format('Y-m-d H:i') }}</td>
                        <td data-label="Action">
                            <a href="{{ route('cursor_autofix.show', $run) }}">ดูรายละเอียด</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">ยังไม่มีรายการ Autofix</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>
        .api-page .badge.queued { background: #eef2ff; color: #3730a3; }
        .api-page .badge.running { background: #ecfeff; color: #155e75; }
        .api-page .badge.skipped { background: #f3f4f6; color: #4b5563; }
    </style>
@endsection
