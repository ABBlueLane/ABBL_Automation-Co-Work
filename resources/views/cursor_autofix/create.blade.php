@extends('api_clients.layout')

@section('title', 'สั่งรัน Cursor Autofix')

@section('content')
    <div class="topbar">
        <div>
            <h1>สั่งรัน Cursor Autofix</h1>
            <p>เลือก IMS issue และ repo ที่ต้องการให้ Cursor CLI เข้าไปแก้</p>
        </div>
        <a class="button secondary" href="{{ route('cursor_autofix.index') }}">กลับรายการ</a>
    </div>

    @if (session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    @if (! $enabled)
        <div class="alert error">ระบบ Autofix ยังปิดอยู่ — ตั้งค่า <code>CURSOR_AUTOFIX_ENABLED=true</code> ก่อนสั่งรัน</div>
    @endif

    <div class="panel" style="margin-bottom: 1rem;">
        <div><strong>โหมดปัจจุบัน:</strong> {{ $dryRun ? 'Dry-run (ไม่เรียก CLI จริง)' : 'Live' }}</div>
    </div>

    <form class="panel" method="post" action="{{ route('cursor_autofix.store') }}">
        @csrf

        <div style="margin-bottom: 1rem;">
            <label for="issue_id" style="display:block; font-weight:600; margin-bottom:.35rem;">Issue ID</label>
            <input
                id="issue_id"
                name="issue_id"
                type="number"
                min="1"
                required
                value="{{ old('issue_id', request('issue_id')) }}"
                style="width:100%; max-width:320px; min-height:38px; border:1px solid var(--vz-border-color); border-radius:.25rem; padding:.47rem .75rem;"
            >
            <div style="margin-top:.35rem; color: var(--vz-secondary-color); font-size:.875rem;">
                ใช้เฉพาะ issue ที่สถานะ <strong>pending (รอรีวิว)</strong>
            </div>
            @error('issue_id')
                <div class="alert error" style="margin-top:.5rem;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="repo_key" style="display:block; font-weight:600; margin-bottom:.35rem;">Repository (optional)</label>
            <select
                id="repo_key"
                name="repo_key"
                style="width:100%; max-width:420px; min-height:38px; border:1px solid var(--vz-border-color); border-radius:.25rem; padding:.47rem .75rem;"
            >
                <option value="">แมปอัตโนมัติจาก URL ของ issue</option>
                @foreach ($repos as $repo)
                    <option value="{{ $repo['key'] }}" @selected(old('repo_key') === $repo['key'])>
                        {{ $repo['name'] }} ({{ $repo['key'] }})
                        @if (! empty($repo['hosts']))
                            — {{ implode(', ', $repo['hosts']) }}
                        @endif
                    </option>
                @endforeach
            </select>
            <div style="margin-top:.35rem; color: var(--vz-secondary-color); font-size:.875rem;">
                ถ้าไม่เลือก ระบบจะใช้ URL ของ issue เช่น gateway.co.th → AB_Gateway
            </div>
            @error('repo_key')
                <div class="alert error" style="margin-top:.5rem;">{{ $message }}</div>
            @enderror
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label style="display:flex; align-items:center; gap:.5rem;">
                <input type="checkbox" name="sync" value="1" @checked(old('sync'))>
                รันทันที (ไม่เข้าคิว)
            </label>
        </div>

        <button type="submit" @disabled(! $enabled)>สร้างงาน Autofix</button>
    </form>
@endsection
