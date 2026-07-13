@php
    $colClass = $colClass ?? 'col-6 col-md-4';
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $url = asset('storage/' . $file);

    if (($cacheBust ?? false) && in_array($ext, ['mp4', 'mov', 'webm'], true)) {
        $fullPath = storage_path('app/public/' . $file);
        if (file_exists($fullPath)) {
            $url .= '?v=' . filemtime($fullPath);
        }
    }

    $sizeLabel = '';
    try {
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($file)) {
            $sizeBytes = \Illuminate\Support\Facades\Storage::disk('public')->size($file);
            $sizeLabel = $sizeBytes >= 1048576
                ? number_format($sizeBytes / 1048576, 1) . ' MB'
                : number_format(max($sizeBytes / 1024, 0), 0) . ' KB';
        }
    } catch (\Throwable $e) {
        $sizeLabel = '';
    }

    // ไอคอน/สี ใช้ชุดเดียวกับ _file_item.blade.php เดิมของระบบ เพื่อความสม่ำเสมอ
    $iconClass = 'ri-file-line';
    $iconColor = 'text-secondary';

    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $videoExts = ['mp4', 'mov', 'webm'];

    if (in_array($ext, $imageExts, true)) {
        $iconClass = 'ri-image-line';
        $iconColor = 'text-info';
    } elseif (in_array($ext, $videoExts, true)) {
        $iconClass = 'ri-video-line';
        $iconColor = 'text-info';
    } elseif ($ext === 'pdf') {
        $iconClass = 'ri-file-pdf-line';
        $iconColor = 'text-danger';
    } elseif (in_array($ext, ['doc', 'docx'], true)) {
        $iconClass = 'ri-file-word-line';
        $iconColor = 'text-primary';
    } elseif (in_array($ext, ['xls', 'xlsx'], true)) {
        $iconClass = 'ri-file-excel-line';
        $iconColor = 'text-success';
    } elseif ($ext === 'csv') {
        $iconClass = 'ri-file-chart-line';
        $iconColor = 'text-warning';
    } elseif ($ext === 'md') {
        $iconClass = 'ri-markdown-line';
        $iconColor = 'text-secondary';
    } elseif (in_array($ext, ['html', 'htm'], true)) {
        $iconClass = 'ri-html5-line';
        $iconColor = 'text-danger';
    } elseif ($ext === 'txt') {
        $iconClass = 'ri-file-text-line';
        $iconColor = 'text-secondary';
    } elseif (in_array($ext, ['json', 'xml'], true)) {
        $iconClass = 'ri-braces-line';
        $iconColor = 'text-info';
    } elseif (in_array($ext, ['css', 'js'], true)) {
        $iconClass = 'ri-file-code-line';
        $iconColor = 'text-warning';
    }
@endphp
<div class="{{ $colClass }}">
    <a href="{{ $url }}" target="_blank" rel="noopener" class="file-card">
        <span class="file-icon-box">
            <i class="{{ $iconClass }} {{ $iconColor }}"></i>
        </span>
        <div class="flex-grow-1 text-truncate">
            <div class="fw-semibold small text-truncate" title="{{ basename($file) }}">
                {{ basename($file) }}
            </div>
            <div class="text-muted" style="font-size:.72rem;">
                {{ strtoupper($ext) }}@if($sizeLabel) • {{ $sizeLabel }}@endif
            </div>
        </div>
    </a>
</div>