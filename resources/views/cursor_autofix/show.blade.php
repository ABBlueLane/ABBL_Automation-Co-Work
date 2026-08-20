@extends('api_clients.layout')

@section('title', 'Autofix #'.$run->id)

@section('content')
    <div class="topbar">
        <div>
            <h1>Autofix #{{ $run->id }}</h1>
            <p>รายละเอียดงาน Cursor CLI สำหรับ IMS issue</p>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <a class="button secondary" href="{{ route('cursor_autofix.index') }}">กลับรายการ</a>
            <a class="button" href="{{ route('cursor_autofix.create', ['issue_id' => $run->issue_id]) }}">สั่งรันใหม่</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="panel" style="margin-bottom: 1rem;">
        <table>
            <tbody>
                <tr>
                    <th style="width: 180px;">สถานะ</th>
                    <td><span class="badge {{ $run->status_badge_class }}">{{ $run->status_label }}</span></td>
                </tr>
                <tr>
                    <th>Issue</th>
                    <td>
                        @if ($run->issue)
                            <div><strong>{{ $run->issue->issue_number }}</strong> — {{ $run->issue->title }}</div>
                            <div style="margin-top:.35rem;">
                                <a href="{{ route('issue.view', [$run->issue->business_id, $run->issue->id]) }}" target="_blank" rel="noopener">เปิดหน้า IMS issue</a>
                            </div>
                            @if ($run->issue->url)
                                <div style="margin-top:.35rem; color: var(--vz-secondary-color);">URL: {{ $run->issue->url }}</div>
                            @endif
                        @else
                            Issue #{{ $run->issue_id }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Repository</th>
                    <td>
                        {{ $run->repo_name ?: '-' }}
                        @if ($run->repo_key)
                            <code>({{ $run->repo_key }})</code>
                        @endif
                        @if ($run->repo_path)
                            <div style="margin-top:.35rem; color: var(--vz-secondary-color);">{{ $run->repo_path }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Matched host</th>
                    <td>{{ $run->matched_host ?: '-' }}</td>
                </tr>
                <tr>
                    <th>Git branch</th>
                    <td>{{ $run->git_branch ?: '-' }}</td>
                </tr>
                <tr>
                    <th>Mode</th>
                    <td>{{ $run->dry_run ? 'dry-run' : 'live' }}</td>
                </tr>
                <tr>
                    <th>Exit code</th>
                    <td>{{ $run->exit_code === null ? '-' : $run->exit_code }}</td>
                </tr>
                <tr>
                    <th>เวลา</th>
                    <td>
                        สร้าง: {{ $run->created_at?->format('Y-m-d H:i:s') ?: '-' }}<br>
                        เริ่ม: {{ $run->started_at?->format('Y-m-d H:i:s') ?: '-' }}<br>
                        จบ: {{ $run->finished_at?->format('Y-m-d H:i:s') ?: '-' }}
                    </td>
                </tr>
                @if ($run->error_message)
                    <tr>
                        <th>Error</th>
                        <td style="color: var(--vz-danger);">{{ $run->error_message }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="panel" style="margin-bottom: 1rem;">
        <h2 style="font-size:1.05rem; margin:0 0 .75rem;">Command</h2>
        <div class="token">{{ is_array($run->command) ? implode(' ', $run->command) : ($run->command ?: '-') }}</div>
    </div>

    <div class="panel" style="margin-bottom: 1rem;">
        <h2 style="font-size:1.05rem; margin:0 0 .75rem;">Prompt</h2>
        <pre style="white-space: pre-wrap; margin:0; font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:.85rem;">{{ $run->prompt ?: '-' }}</pre>
    </div>

    <div class="panel" style="margin-bottom: 1rem;">
        <h2 style="font-size:1.05rem; margin:0 0 .75rem;">Stdout</h2>
        <pre style="white-space: pre-wrap; margin:0; font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:.85rem;">{{ $run->stdout ?: '-' }}</pre>
    </div>

    <div class="panel">
        <h2 style="font-size:1.05rem; margin:0 0 .75rem;">Stderr</h2>
        <pre style="white-space: pre-wrap; margin:0; font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:.85rem;">{{ $run->stderr ?: '-' }}</pre>
    </div>

    <style>
        .api-page .badge.queued { background: #eef2ff; color: #3730a3; }
        .api-page .badge.running { background: #ecfeff; color: #155e75; }
        .api-page .badge.skipped { background: #f3f4f6; color: #4b5563; }
    </style>
@endsection
