@php
    $colClass = $colClass ?? 'col-md-3 mb-3';
    $isBase64 = $isBase64 ?? Str::startsWith($file, 'data:');
    $pdfPreview = $pdfPreview ?? false;
    $cacheBust = $cacheBust ?? false;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $url = $isBase64 ? $file : asset('storage/' . $file);

    if ($cacheBust && ! $isBase64 && in_array($ext, ['mp4', 'mov', 'webm'], true)) {
        $fullPath = storage_path('app/public/' . $file);
        if (file_exists($fullPath)) {
            $url .= '?v=' . filemtime($fullPath);
        }
    }

    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $videoExts = ['mp4', 'mov', 'webm'];
    $downloadExts = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv',
        'md', 'html', 'htm', 'txt', 'json', 'xml', 'css', 'js',
    ];
    $isImage = in_array($ext, $imageExts, true) || ($isBase64 && preg_match('/image/i', $file));
    $isVideo = in_array($ext, $videoExts, true) || ($isBase64 && preg_match('/video/i', $file));

    if ($isBase64 && ! in_array($ext, $downloadExts, true)) {
        if (Str::contains($file, 'pdf')) {
            $ext = 'pdf';
        } elseif (Str::contains($file, 'wordprocessingml.document')) {
            $ext = 'docx';
        } elseif (Str::contains($file, 'word') || Str::contains($file, 'msword')) {
            $ext = 'doc';
        } elseif (Str::contains($file, 'excel') || Str::contains($file, 'sheet')) {
            $ext = 'xls';
        } elseif (Str::contains($file, 'csv')) {
            $ext = 'csv';
        } elseif (Str::contains($file, 'markdown')) {
            $ext = 'md';
        } elseif (Str::contains($file, 'html')) {
            $ext = 'html';
        } elseif (Str::contains($file, 'json')) {
            $ext = 'json';
        } elseif (Str::contains($file, 'xml')) {
            $ext = 'xml';
        }
    }
@endphp

<div class="{{ $colClass }}">
    @if ($isImage)
        <a href="{{ $url }}" data-fancybox="gallery">
            <img src="{{ $url }}" class="img-fluid rounded border" alt="">
        </a>
    @elseif ($isVideo)
        <video controls class="w-100 rounded border">
            <source src="{{ $url }}">
        </video>
    @elseif ($ext === 'pdf' && $pdfPreview && ! $isBase64)
        <iframe src="{{ $url }}" class="w-100 rounded border" style="height:200px;" title="{{ basename($file) }}"></iframe>
    @elseif (in_array($ext, $downloadExts, true) || ! $isBase64)
        <a href="{{ $url }}" target="_blank" rel="noopener"
            class="d-flex flex-column align-items-center justify-content-center border rounded p-3 text-center h-100 bg-light text-decoration-none">
            @if ($ext === 'pdf')
                <i class="ri-file-pdf-line text-danger" style="font-size:40px;"></i>
            @elseif (in_array($ext, ['doc', 'docx'], true))
                <i class="ri-file-word-line text-primary" style="font-size:40px;"></i>
            @elseif (in_array($ext, ['xls', 'xlsx'], true))
                <i class="ri-file-excel-line text-success" style="font-size:40px;"></i>
            @elseif ($ext === 'csv')
                <i class="ri-file-chart-line text-warning" style="font-size:40px;"></i>
            @elseif ($ext === 'md')
                <i class="ri-markdown-line text-secondary" style="font-size:40px;"></i>
            @elseif (in_array($ext, ['html', 'htm'], true))
                <i class="ri-html5-line text-danger" style="font-size:40px;"></i>
            @elseif ($ext === 'txt')
                <i class="ri-file-text-line text-secondary" style="font-size:40px;"></i>
            @elseif (in_array($ext, ['json', 'xml'], true))
                <i class="ri-braces-line text-info" style="font-size:40px;"></i>
            @elseif (in_array($ext, ['css', 'js'], true))
                <i class="ri-file-code-line text-warning" style="font-size:40px;"></i>
            @else
                <i class="ri-file-line text-secondary" style="font-size:40px;"></i>
            @endif
            <div class="mt-2 small text-truncate w-100 text-dark">
                {{ $isBase64 ? 'Preview File' : basename($file) }}
            </div>
            <small class="text-muted mt-1">ไฟล์แนบ</small>
        </a>
    @endif
</div>
