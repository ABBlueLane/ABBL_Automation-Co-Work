@extends('api_clients.layout')

@section('title', 'Application Logs')

@section('content')
    <div class="topbar">
        <div>
            <h1>Application Logs</h1>
            <p>ดู log จาก <code>storage/logs</code> — อัปเดตล่าสุด {{ $updatedAt }} ({{ $fileSize }})</p>
        </div>
        <a class="button secondary" href="{{ route('logs.index', array_merge(request()->query(), ['file' => $file])) }}">Refresh</a>
    </div>

    <div class="panel mb-3">
        <form method="GET" action="{{ route('logs.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="file">ไฟล์</label>
                <select name="file" id="file">
                    @foreach ($files as $logFile)
                        <option value="{{ $logFile }}" @selected($logFile === $file)>{{ $logFile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="lines">จำนวนบรรทัด</label>
                <select name="lines" id="lines">
                    @foreach ([100, 250, 500, 1000, 2000] as $option)
                        <option value="{{ $option }}" @selected((int) $lines === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="level">Level</label>
                <select name="level" id="level">
                    <option value="">ทั้งหมด</option>
                    @foreach (['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'] as $option)
                        <option value="{{ $option }}" @selected($level === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="q">ค้นหา</label>
                <input type="text" name="q" id="q" value="{{ $search }}" placeholder="เช่น LINE push failed">
            </div>
            <div class="col-md-2">
                <button type="submit" class="button w-100">แสดงผล</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <pre class="log-output mb-0">{{ $content }}</pre>
    </div>
@endsection

@push('styles')
    <style>
        .log-output {
            max-height: 70vh;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            line-height: 1.45;
            background: #111827;
            color: #e5e7eb;
            border-radius: .25rem;
            padding: 1rem;
        }
    </style>
@endpush
