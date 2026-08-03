@php
    $priorityMeta = \App\Models\Issue::getPriorityMeta($issue->priority ?? null);
    $priorityCaptions = [
        \App\Models\Issue::PRIORITY_HIGH => 'เร่งด่วน',
        \App\Models\Issue::PRIORITY_MEDIUM => 'ปกติ',
        \App\Models\Issue::PRIORITY_LOW => 'ต่ำ',
    ];
    $priorityColors = [
        \App\Models\Issue::PRIORITY_HIGH => '#dc3545',
        \App\Models\Issue::PRIORITY_MEDIUM => '#ffc107',
        \App\Models\Issue::PRIORITY_LOW => '#28a745',
    ];
    $priorityColor = $priorityColors[$issue->priority ?? ''] ?? '#6c757d';
    $priorityCaption = $priorityCaptions[$issue->priority ?? ''] ?? ($priorityMeta['label'] ?? '-');
    $files = array_filter((array) data_get($issue, 'firstComment.files', []));
@endphp

<div class="review-summary">
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="review-field-box">
                <div class="review-field-label"><i class="ri-edit-line me-1"></i> ปัญหา</div>
                <div class="review-field-value">{{ $issue->title }}</div>

                <div class="review-field-label mt-3"><i class="ri-building-2-line me-1"></i> โปรเจค</div>
                <div class="review-field-value">{{ $issueProject?->name ?? '-' }}</div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="review-field-box text-center">
                <div class="review-field-label mb-2">ระดับความเร่งด่วน</div>
                <div class="d-inline-flex flex-column align-items-center">
                    <span class="review-priority-dot" style="background-color: {{ $priorityColor }};"></span>
                    <small class="text-muted mt-1">{{ $priorityCaption }}</small>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="review-field-box">
                <div class="review-field-label"><i class="ri-file-text-line me-1"></i> รายละเอียด</div>
                <div class="review-field-value">{!! nl2br(e($issue->firstComment?->comment ?? '-')) !!}</div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="review-field-box">
                <div class="review-field-label"><i class="ri-attachment-2 me-1"></i> แนบไฟล์</div>
                @if (count($files) > 0)
                    <div class="row mt-1">
                        @foreach ($files as $file)
                            @include('public.issue._file_item', [
                                'file' => $file,
                                'pdfPreview' => true,
                            ])
                        @endforeach
                    </div>
                @else
                    <div class="review-field-value text-muted">-</div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="review-field-box">
                <div class="review-field-label"><i class="ri-links-line me-1"></i> แนบลิงก์</div>
                <div class="review-field-value">
                    @if ($issue->url)
                        <a href="{{ $issue->url }}" target="_blank" class="text-primary fw-semibold">{{ $issue->url }}</a>
                    @else
                        <span class="text-muted">ไม่มี URL สำหรับการแจ้งปัญหานี้</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
