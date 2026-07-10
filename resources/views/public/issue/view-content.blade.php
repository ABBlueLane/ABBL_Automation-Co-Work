@php
    /**
     * ตัวแปรที่ได้จาก IssueController@preview:
     *   $issue     -> instance ของ Issue (ยังไม่ถูกบันทึกจริง) พร้อม relation:
     *                 creator, assignee (null), firstComment (มี comment, files)
     *   $comments  -> collection ว่าง (ยังไม่มี comment อื่นในโหมด preview)
     *   $business  -> business id (string)
     */

    $statusMeta = \App\Models\Issue::getStatusMeta($issue->status);
    $files      = (array) ($issue->firstComment->files ?? []);

    $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $videoExt = ['mp4', 'webm', 'mov'];
@endphp

<div class="review-wrap">

    <div class="review-banner">
        <i class="ri-eye-line"></i>
        <span>Preview Mode — ยังไม่ได้บันทึกข้อมูล</span>
    </div>

    <div class="review-card">

        {{-- Header --}}
        <div class="review-head">
            <div class="review-head-left">
                <span class="review-icon"><i class="ri-bug-line"></i></span>
                <div>
                    <h5 class="review-title">{{ $issue->title ?: '-' }}</h5>
                    <span class="review-badge review-badge-status {{ $statusMeta['class'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                </div>
            </div>
            {!! $issue->getPriorityBadgeHtml() !!}
        </div>

        {{-- Description --}}
        <div class="review-section">
            <span class="review-label"><i class="ri-file-text-line"></i> รายละเอียด</span>
            <p class="review-text">{{ $issue->firstComment->comment ?: '—' }}</p>
        </div>

        {{-- Meta grid --}}
        <div class="review-meta-grid">
            <div class="review-meta-item">
                <span class="review-label"><i class="ri-user-line"></i> ผู้สร้าง</span>
                <span class="review-value">{{ $issue->creator?->full_name ?? '-' }}</span>
            </div>
            <div class="review-meta-item">
                <span class="review-label"><i class="ri-user-follow-line"></i> ผู้รับผิดชอบ</span>
                <span class="review-value">{{ $issue->assignee?->full_name ?? '-' }}</span>
            </div>
            <div class="review-meta-item">
                <span class="review-label"><i class="ri-calendar-line"></i> วันที่สร้าง</span>
                <span class="review-value">{{ optional($issue->created_at)->format('d/m/Y H:i') ?? '-' }}</span>
            </div>
            @if($issue->url)
                <div class="review-meta-item review-meta-wide">
                    <span class="review-label"><i class="ri-links-line"></i> ลิงก์</span>
                    <a href="{{ $issue->url }}" target="_blank" class="review-value review-link">{{ $issue->url }}</a>
                </div>
            @endif
        </div>

        {{-- Attachments --}}
        @if(count($files))
            <div class="review-section">
                <span class="review-label"><i class="ri-attachment-2"></i> ไฟล์แนบ ({{ count($files) }})</span>
                <div class="review-files">
                    @foreach($files as $f)
                        @php
                            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                            $name = basename($f);
                        @endphp

                        @if(in_array($ext, $imageExt))
                            <a href="/storage/{{ $f }}" target="_blank" class="review-file-thumb">
                                <img src="/storage/{{ $f }}" alt="{{ $name }}">
                            </a>
                        @elseif(in_array($ext, $videoExt))
                            <div class="review-file-thumb review-file-video">
                                <video src="/storage/{{ $f }}" controls></video>
                            </div>
                        @else
                            <a href="/storage/{{ $f }}" target="_blank" class="review-file-chip">
                                <i class="ri-file-3-line"></i>
                                <span>{{ $name }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>

<style>
    .review-wrap { font-family: inherit; }

    .review-banner {
        display: flex; align-items: center; gap: 8px;
        background: #fef9e7; color: #92720b;
        border: 1px solid #f5e2a0;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: .9rem;
        margin-bottom: 16px;
    }

    .review-card {
        border: 1px solid #eef0f3;
        border-radius: 16px;
        padding: 24px 28px;
        background: #fff;
    }

    .review-head {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 12px;
        padding-bottom: 18px;
        border-bottom: 1px solid #f0f1f4;
        margin-bottom: 20px;
    }
    .review-head-left { display: flex; align-items: center; gap: 14px; }
    .review-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: var(--im-primary-soft, #eef0fe);
        color: var(--im-primary, #4f46e5);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .review-title { font-weight: 800; margin: 0 0 6px; color: #1f2937; }

    .review-badge {
        display: inline-block;
        font-size: .72rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 999px;
        letter-spacing: .01em;
    }
    .review-badge-status { background: #fff4d6; color: #9a6b00; }

    .review-section { margin-bottom: 22px; }
    .review-section:last-child { margin-bottom: 0; }

    .review-label {
        display: flex; align-items: center; gap: 6px;
        font-size: .78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        color: #9aa1ab;
        margin-bottom: 8px;
    }

    .review-text {
        margin: 0; color: #1f2937;
        font-size: .95rem; line-height: 1.6;
        white-space: pre-line;
    }

    .review-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px 24px;
        padding: 20px 0;
        border-top: 1px solid #f0f1f4;
        border-bottom: 1px solid #f0f1f4;
        margin-bottom: 20px;
    }
    .review-meta-wide { grid-column: 1 / -1; }
    .review-meta-item { display: flex; flex-direction: column; }
    .review-value { color: #1f2937; font-weight: 600; font-size: .92rem; }
    .review-link { color: var(--im-primary, #4f46e5); text-decoration: none; word-break: break-all; }
    .review-link:hover { text-decoration: underline; }

    .review-files { display: flex; flex-wrap: wrap; gap: 10px; }

    .review-file-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: #f8f9fa; border: 1px solid #eef0f3;
        border-radius: 8px; padding: 6px 12px;
        font-size: .82rem; color: #374151;
        text-decoration: none;
    }
    .review-file-chip:hover { background: #f0f1f4; color: #1f2937; }
    .review-file-chip i { color: #9aa1ab; }

    .review-file-thumb {
        width: 96px; height: 96px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #eef0f3;
        display: block;
    }
    .review-file-thumb img,
    .review-file-thumb video {
        width: 100%; height: 100%; object-fit: cover;
    }
    .review-file-video video { object-fit: contain; background: #000; }

    @media (max-width: 768px) {
        .review-meta-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>