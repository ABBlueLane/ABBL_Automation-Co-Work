@extends('layouts.public')
@section('navbar_container', 'container')
@section('content')
    <style>
        /* ============================================================
           REVIEW ISSUE REPORT — matched to reference screenshot
        ============================================================ */

        body, .container.py-4 {
            background: #f6f7f9;
        }

        .btn-copy-link-icon {
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #b0b5bd;
            font-size: 1.1rem;
            cursor: pointer;
            transition: color 0.15s ease, transform 0.15s ease;
        }
        .btn-copy-link-icon:hover {
            color: #3762f0;
            transform: scale(1.1);
        }
        .btn-copy-link-icon:focus {
            outline: none;
        }

        /* ---------- Outer shell ---------- */
        .review-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfc 100%);
            border-radius: 24px;
            box-shadow: 0 1px 3px rgba(17, 24, 39, .06);
            padding: 34px 36px;
            margin-bottom: 24px;
        }

        .review-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 30px;
        }

        .review-title {
            font-weight: 800;
            font-size: 1.85rem;
            color: #181b21;
            letter-spacing: -0.01em;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
        }

        .review-issue-number {
            color: #9aa1ac;
            font-size: .85rem;
            font-weight: 500;
        }

        /* plain-text nav links (no button chrome) */
        .review-nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            padding-top: 10px;
            flex-wrap: wrap;
        }
        .review-nav-links a,
        .review-nav-links button {
            background: none;
            border: none;
            color: #6b7280;
            font-size: .9rem;
            font-weight: 500;
            text-decoration: none;
            padding: 0;
            white-space: nowrap;
        }
        .review-nav-links a:hover,
        .review-nav-links button:hover {
            color: #111827;
        }
        .review-nav-links .nav-approve-close {
            color: #111827;
            font-weight: 600;
        }

        .review-section-title {
            font-weight: 700;
            font-size: 1rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
        }

        .review-section-title i {
            color: #9aa1ac;
            font-size: 1.05rem;
        }

        .info-field-label {
            color: #9aa1ac;
            font-size: .78rem;
            margin-bottom: 6px;
        }

        .info-field-value {
            color: #1f2937;
            font-size: .95rem;
            font-weight: 500;
        }

        .review-info-block + .review-info-block {
            margin-top: 22px;
        }

        .avatar-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #e0e7ff;
            color: #3762f0;
            font-size: .75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ---------- Priority pill ---------- */
        .priority-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
        }
        .priority-pill-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }



        /* ---------- Details (plain text, no box) ---------- */
        .review-details-text {
            color: #374151;
            font-size: .92rem;
            line-height: 1.7;
        }

        /* ---------- Attach panels ---------- */
        .attach-panel {
            background: #f0f1f3;
            border-radius: 18px;
            padding: 18px;
            height: 100%;
        }

        .link-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .75rem 1rem;
            background: #fff;
            border: 1px solid #ececef;
            border-radius: 12px;
            color: #1f2937;
            text-decoration: none;
            font-size: .88rem;
        }
        .link-row:hover {
            background: #fafafb;
            color: #1f2937;
        }
        .link-row + .link-row {
            margin-top: .55rem;
        }
        .link-row .link-row-icon {
            width: 20px;
            display: inline-flex;
            justify-content: center;
            color: #6b7280;
        }
        .link-row .link-row-text {
            display: flex;
            align-items: center;
            gap: .6rem;
            overflow: hidden;
        }

        .file-card {
            background: #fff;
            border: 1px solid #ececef;
            border-radius: 14px;
            padding: .7rem .85rem;
            display: flex;
            align-items: center;
            gap: .65rem;
            height: 100%;
            text-decoration: none;
            color: #1f2937;
        }
        .file-card:hover {
            background: #fafafb;
            color: #1f2937;
        }
        .file-card .file-name {
            font-size: .82rem;
            font-weight: 600;
            line-height: 1.25;
            word-break: break-word;
        }
        .file-card .file-meta {
            font-size: .72rem;
            color: #9aa1ac;
            text-transform: uppercase;
        }

        .file-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .file-icon-box.icon-image { background: #eef0ff; color: #5b6bf5; }
        .file-icon-box.icon-doc   { background: #eaf1ff; color: #3762f0; }
        .file-icon-box.icon-sheet { background: #e8f8ee; color: #1fa15a; }
        .file-icon-box.icon-generic { background: #f0f1f3; color: #6b7280; }

        /* ---------- Discussion card ---------- */
        .review-card {
            background: #fff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(17, 24, 39, .05);
        }

        .discussion-comment {
            display: flex;
            gap: .75rem;
            padding: 0 0 1.1rem;
        }
        .discussion-comment + .discussion-comment {
            margin-top: .25rem;
            padding-top: 1.1rem;
            border-top: 1px solid #f1f3f5;
        }

        .comment-compose {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: .85rem 1rem;
            margin-top: 1.4rem;
        }

        .comment-compose textarea {
            border: none;
            resize: none;
            box-shadow: none !important;
            padding: 0;
            font-size: .9rem;
        }

        .comment-compose textarea:focus {
            outline: none;
        }

        .comment-compose-icon-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: #9aa1ac;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .comment-compose-icon-btn:hover {
            background: #f4f5f7;
            color: #374151;
        }

        .btn-send-pill {
            background: #3762f0;
            border-color: #3762f0;
            border-radius: 999px;
            padding: .4rem 1.3rem;
            font-weight: 600;
        }
        .btn-send-pill:hover {
            background: #2c4fd6;
            border-color: #2c4fd6;
        }

        .back-to-edit-btn {
            border-radius: 999px;
            padding: .55rem 1.4rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #374151;
            font-weight: 500;
        }
        .back-to-edit-btn:hover {
            background: #f9fafb;
            color: #374151;
        }
    </style>

    <div class="container py-4">

        @if ($issue->status === \App\Models\Issue::STATUS_DRAFT && (int) $issue->created_by === (int) auth()->id())
            <div class="alert alert-warning mb-3">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-semibold mb-1">
                            <i class="ri-eye-line me-1"></i>
                            รีวิวแบบร่าง
                        </div>
                        <p class="mb-0 small">
                            ตรวจสอบหัวข้อ รายละเอียด ลิงก์ และไฟล์แนบด้านล่าง — ยังไม่เข้าคิวทีมงานจนกว่าคุณจะกด
                            <strong>ส่งเข้าระบบ</strong>
                            หากต้องการแก้ไขก่อน ให้กด <strong>แก้ไขร่าง</strong>
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                        <a href="{{ route('issue.create', $business) }}?draft={{ $issue->id }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-edit-line me-1"></i> แก้ไขร่าง
                        </a>
                        <button type="button" class="btn btn-sm btn-success" id="submitDraftFromView">
                            <i class="ri-send-plane-line me-1"></i> ส่งเข้าระบบ
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @php
            $statusMeta = \App\Models\Issue::getStatusMeta($issue->status);
            $issueViewUrl = route('issue.view', [$business, $issue->id]);

            $priorityBadgeStyles = [
                \App\Models\Issue::PRIORITY_HIGH ?? 'high' => ['label' => 'เร่งด่วน', 'bg' => 'rgba(217,72,72,0.12)', 'color' => '#b83b3b', 'dot' => '#d94848'],
                \App\Models\Issue::PRIORITY_MEDIUM ?? 'medium' => ['label' => 'ปานกลาง', 'bg' => 'rgba(242,169,59,0.15)', 'color' => '#92620a', 'dot' => '#f2a93b'],
                \App\Models\Issue::PRIORITY_LOW ?? 'low' => ['label' => 'ไม่เร่งด่วน', 'bg' => 'rgba(22,163,74,0.12)', 'color' => '#15803d', 'dot' => '#16a34a'],
            ];
            $priorityBadge = $priorityBadgeStyles[$issue->priority] ?? ['label' => '-', 'bg' => '#f3f4f6', 'color' => '#6b7280', 'dot' => '#9ca3af'];
        @endphp

        {{-- ============ SHELL: header + info in one card ============ --}}
        <div class="review-shell">

            {{-- HEADER --}}
            <div class="review-header-row">
                <div>
                    <div class="review-title">
                        {{ $issue->issue_number }}
                        <button type="button" class="btn-copy-link-icon ms-2" title="คัดลอกลิงก์"
                            aria-label="คัดลอกลิงก์" onclick="copyText('{{ $issueViewUrl }}');">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>
                    <div class="review-issue-number">
                        #{{ $issue->issue_number }}
                    </div>
                </div>

                <div class="review-nav-links">
                    @if ($issue->status != \App\Models\Issue::STATUS_DONE && $issue->status !== \App\Models\Issue::STATUS_DRAFT)
                        <button id="closeIssueBtn" class="nav-approve-close" type="button">
                            Approve &amp; Close Issue
                        </button>
                    @endif
                    <a href="{{ route('issue.index', $business) }}">Home</a>
                    <a href="{{ route('issue.duplicate', [$business, $issue->id]) }}" class="btn-duplicate">Duplicate</a>
                    <a href="{{ route('issue.create', $business) }}">Create New</a>
                </div>
            </div>

            <div class="review-section-title">
                <i class="ri-information-line"></i> หัวข้อปัญหา
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="review-info-block">
                        <div class="info-field-label">โปรเจค</div>
                        <div class="info-field-value">
                            <i class="ri-building-2-line me-1 text-muted"></i>
                            {{ $business->business_name ?? '-' }}
                        </div>
                    </div>
                    <div class="review-info-block">
                        <div class="info-field-label">เนื่องวันที่</div>
                        <div class="info-field-value">
                            {{ $issue->created_at->format('d/m/y • H:i A') }}
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="review-info-block">
                        <div class="info-field-label">ระดับความเร่งด่วน</div>
                        <span class="priority-pill" style="background: {{ $priorityBadge['bg'] }}; color: {{ $priorityBadge['color'] }};">
                            <span class="priority-pill-dot" style="background: {{ $priorityBadge['dot'] }};"></span>
                            {{ $priorityBadge['label'] }}
                        </span>
                    </div>
                    <div class="review-info-block">
                        <div class="info-field-label">ผู้รับผิดชอบ</div>
                        <div class="info-field-value d-flex align-items-center gap-2">
                            <span class="avatar-circle">
                                @if ($issue->assignee?->avatar_url)
                                    <img src="{{ $issue->assignee->avatar_url }}" alt="">
                                @else
                                    {{ mb_substr($issue->assignee->full_name ?? '-', 0, 1) }}
                                @endif
                            </span>
                            {{ $issue->assignee->full_name ?? '-' }}
                        </div>
                    </div>

                    @if ($issue->status !== \App\Models\Issue::STATUS_DRAFT)
                        @php
                            $statusSteps = [
                                \App\Models\Issue::STATUS_PENDING => 'รอรีวิว',
                                \App\Models\Issue::STATUS_IN_PROGRESS => 'กำลังดำเนินการ',
                                'reviewing' => 'รอตรวจ',
                                \App\Models\Issue::STATUS_CUSTOMER_REPLIED => 'ลูกค้าตอบกลับ',
                                \App\Models\Issue::STATUS_DONE => 'ดำเนินการแล้ว',
                            ];
                            $stepKeys = array_keys($statusSteps);
                            $currentIndex = array_search($issue->status, $stepKeys);
                            $currentIndex = $currentIndex === false ? 0 : $currentIndex;
                            $totalSteps = count($stepKeys);
                            $linePercent = $totalSteps > 1 ? ($currentIndex / ($totalSteps - 1)) * 100 : 0;
                            
                            $statusMeta = [
                                \App\Models\Issue::STATUS_PENDING => ['bg' => 'background-color: #f59e0b; color: #fff;', 'color' => '#f59e0b'],
                                \App\Models\Issue::STATUS_IN_PROGRESS => ['bg' => 'background-color: #3b82f6; color: #fff;', 'color' => '#3b82f6'],
                                'reviewing' => ['bg' => 'background-color: #14b8a6; color: #fff;', 'color' => '#14b8a6'],
                                \App\Models\Issue::STATUS_CUSTOMER_REPLIED => ['bg' => 'background-color: #8b5cf6; color: #fff;', 'color' => '#8b5cf6'],
                                \App\Models\Issue::STATUS_DONE => ['bg' => 'background-color: #16a34a; color: #fff;', 'color' => '#16a34a'],
                            ];
                            
                            $currentStatus = $issue->status ?: \App\Models\Issue::STATUS_PENDING;
                            $currentMeta = $statusMeta[$currentStatus] ?? $statusMeta[\App\Models\Issue::STATUS_PENDING];
                            $statusLabel = $statusSteps[$currentStatus] ?? 'รอรีวิว';
                        @endphp
                        <div class="review-info-block">
                            <div class="d-flex align-items-center gap-1 mb-2">
                                <span class="info-field-label mb-0">สถานะ:</span> 
                                <span class="badge rounded-pill px-2 py-1" style="{{ $currentMeta['bg'] }} font-size: 0.75rem; font-weight: 500;">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            
                            <div class="position-relative d-flex justify-content-between align-items-center my-3" style="height: 20px;">
                                <div class="position-absolute start-0 end-0" style="height: 4px; background-color: #e9ecef; top: 50%; transform: translateY(-50%); z-index: 1; border-radius: 2px;"></div>
                                <div class="position-absolute start-0" style="height: 4px; width: {{ $linePercent }}%; background-color: {{ $currentMeta['color'] }}; top: 50%; transform: translateY(-50%); z-index: 2; transition: width 0.4s ease; border-radius: 2px;"></div>
                                
                                @for ($idx = 0; $idx < 5; $idx++)
                                    @php
                                        $active = $currentIndex >= $idx;
                                        $dotColor = $active ? $currentMeta['color'] : '#fff';
                                        $dotBorder = $active ? $currentMeta['color'] : '#ced4da';
                                    @endphp
                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: {{ $dotColor }}; border-color: {{ $dotBorder }} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                @endfor
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="review-section-title mt-2">
                <i class="ri-file-text-line"></i> รายละเอียด
            </div>
            <div class="review-details-text mb-4">
                {!! nl2br(e($issue->firstComment->comment ?? '-')) !!}
            </div>

            @php
                $issueFiles = (array) (data_get($issue, 'firstComment.files') ?: []);
            @endphp
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="attach-panel">
                        <div class="review-section-title mb-3">
                            <i class="ri-links-line"></i> แนบลิงก์
                        </div>
                        @if ($issue->url)
                            <a href="{{ $issue->url }}" target="_blank" class="link-row">
                                <span class="link-row-text text-truncate">
                                    <i class="ri-layout-grid-line link-row-icon"></i>
                                    {{ $issue->url }}
                                </span>
                                <i class="ri-external-link-line text-muted"></i>
                            </a>
                        @else
                            <div class="text-muted small">ไม่มีลิงก์ที่เกี่ยวข้อง</div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="attach-panel">
                        <div class="review-section-title mb-3">
                            <i class="ri-attachment-2"></i> แนบไฟล์
                        </div>
                        @if (!empty($issueFiles))
                            <div class="row g-2">
                                @foreach ($issueFiles as $file)
                                    @include('public.issue._file_item', ['file' => $file])
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small">ไม่มีไฟล์แนบ</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ DISCUSSION ============ --}}
        <div class="review-card mb-4">
            <div class="card-body p-4">

                <div class="review-section-title mb-3">
                    <i class="ri-chat-3-line"></i> Discussion
                </div>

                @forelse ($comments as $comment)
                    <div class="discussion-comment">
                        <span class="avatar-circle" style="width:36px;height:36px;font-size:.95rem;">
                            @if ($comment->user->avatar_url ?? false)
                                <img src="{{ $comment->user->avatar_url }}" alt="">
                            @else
                                {{ mb_substr($comment->user->full_name ?? $comment->user->name ?? '-', 0, 1) }}
                            @endif
                        </span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="fw-bold">
                                    {{ $comment->user->full_name ?? $comment->user->name }}
                                </div>
                                <div class="text-muted small flex-shrink-0 ms-2">
                                    {{ $comment->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="mt-1">
                                {!! nl2br(e($comment->comment)) !!}
                            </div>
                            @php
                                $files = (array) ($comment->files ?: []);
                            @endphp
                            @if (!empty($files))
                                <div class="row g-2 mt-2">
                                    @foreach ($files as $file)
                                        @include('public.issue._file_item', [
                                            'file' => $file,
                                            'colClass' => 'col-md-4 mb-2',
                                            'cacheBust' => true,
                                        ])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted small mb-3">ยังไม่มีความคืบหน้า</div>
                @endforelse

                <div class="mt-3">
                    {{ $comments->links() }}
                </div>

                @if ($issue->status !== \App\Models\Issue::STATUS_DONE && $issue->status !== \App\Models\Issue::STATUS_DRAFT)
                    <form id="commentForm">
                        @csrf
                        <div class="comment-compose">
                            <textarea name="comment" class="form-control" rows="2"
                                placeholder="Write a comment, use @ to mention someone..."></textarea>

                            <div id="commentAttachArea" class="d-none mt-2 mb-2">
                                <div class="dropzone border rounded-3 p-3 text-center bg-light small"
                                    id="dropzoneUpload" tabindex="0">
                                    <div class="dz-message">
                                        <i class="ri-upload-cloud-2-line fs-3 text-muted"></i>
                                        <p class="mt-1 mb-1">
                                            ลากไฟล์มาวาง หรือ
                                            <span id="browseTrigger2" class="text-primary fw-semibold"
                                                style="cursor:pointer;">
                                                คลิกที่นี่
                                            </span>
                                            หรือ <b>Ctrl + V</b> เพื่อวางภาพ
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end gap-1 mt-2">
                                <button type="button" id="commentAttachToggle"
                                    class="comment-compose-icon-btn" title="แนบไฟล์หรือรูปภาพ">
                                    <i class="ri-emotion-line"></i>
                                </button>
                                <button class="btn btn-send-pill btn-primary btn-sm" type="submit">
                                    Send
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <a href="{{ ($issue->status === \App\Models\Issue::STATUS_DRAFT && (int) $issue->created_by === (int) auth()->id()) ? route('issue.create', $business) . '?draft=' . $issue->id : route('issue.index', $business) }}"
            class="back-to-edit-btn">
            <i class="ri-arrow-left-line me-1"></i> Back to Edit
        </a>

    </div>
@endsection


@section('script')
    <script>
        Fancybox.bind("[data-fancybox='gallery']");
        Dropzone.autoDiscover = false;
        let files = [];
        let uploadedFiles = [];
        let dz = null;

        function initCommentDropzone() {
            if (dz) return;
            const el = document.getElementById('dropzoneUpload');
            if (!el) return;

            dz = new Dropzone("#dropzoneUpload", {
                url: "#",
                autoProcessQueue: false,
                acceptedFiles: `
image/*,
video/mp4,video/webm,video/quicktime,
.pdf,.doc,.docx,.xls,.xlsx,.csv,
.md,.html,.htm,.txt,.json,.xml,.css,.js
`,
                addRemoveLinks: true,
                clickable: "#browseTrigger2",
            });

            dz.on("addedfile", function(file) {

                const attachArea = document.getElementById('commentAttachArea');
                if (attachArea) attachArea.classList.remove('d-none');

                let formData = new FormData();
                formData.append("file", file);
                formData.append("_token", "{{ csrf_token() }}");

                fetch("{{ route('issue.upload', $business) }}", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        uploadedFiles.push(res.path);
                        console.log("Uploaded:", res.path);
                    })
                    .catch(err => {
                        console.error(err);
                        alert("อัปโหลดไฟล์ไม่สำเร็จ");
                    });
            });

            dz.on("removedfile", function(file) {
                uploadedFiles = uploadedFiles.filter(path => {
                    return !path.endsWith(file.name);
                });

                const attachArea = document.getElementById('commentAttachArea');
                if (attachArea && dz.files.length === 0) {
                    attachArea.classList.add('d-none');
                }
            });

            const dropzoneElement = document.getElementById('dropzoneUpload');

            dropzoneElement.addEventListener('click', () => {
                dropzoneElement.focus();
            });

            dropzoneElement.addEventListener('focus', () => {
                dropzoneElement.classList.add('border-primary');
            });

            dropzoneElement.addEventListener('blur', () => {
                dropzoneElement.classList.remove('border-primary');
            });

            dropzoneElement.addEventListener('paste', function(e) {
                const items = (e.clipboardData || window.clipboardData).items;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        const file = items[i].getAsFile();
                        dz.addFile(file);
                    }
                }
            });

            const attachToggleBtn = document.getElementById('commentAttachToggle');
            if (attachToggleBtn) {
                attachToggleBtn.addEventListener('click', function() {
                    const attachArea = document.getElementById('commentAttachArea');
                    if (attachArea) attachArea.classList.remove('d-none');
                    document.getElementById('browseTrigger2').click();
                });
            }

            const commentTextareaEl = document.querySelector('#commentForm textarea[name="comment"]');
            if (commentTextareaEl) {
                commentTextareaEl.addEventListener('paste', function(e) {
                    const items = (e.clipboardData || window.clipboardData).items;
                    for (let i = 0; i < items.length; i++) {
                        if (items[i].type.indexOf('image') !== -1) {
                            const file = items[i].getAsFile();
                            dz.addFile(file);
                        }
                    }
                });
            }
        }

        initCommentDropzone();

        $("#commentForm").submit(function(e) {
            e.preventDefault();
            let formData = new FormData();
            formData.append("comment", $("textarea[name=comment]").val());
            uploadedFiles.forEach(path => {
                formData.append("files[]", path);
            });
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            $.ajax({
                url: "{{ route('issue.comment.store', [$business, $issue->id]) }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    $("textarea[name=comment]").val("");
                    files = [];
                    if (dz) {
                        dz.removeAllFiles();
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                },
                error: function() {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกได้', 'error');
                }
            });
        });

        $("#closeIssueBtn").click(function() {
            Swal.fire({
                title: 'ยืนยันปิดเคส?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ปิดเคส',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('issue.close', [$business, $issue->id]) }}",
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        success: function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'ปิดเคสแล้ว',
                                timer: 1200,
                                showConfirmButton: false
                            });
                            setTimeout(() => {
                                location.reload();
                            }, 1000)
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-duplicate', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            Swal.fire({
                title: 'Duplicate Issue?',
                text: 'ระบบจะคัดลอกข้อมูลไปหน้าใหม่',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Duplicate'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });

        @if ($issue->status === \App\Models\Issue::STATUS_DRAFT && (int) $issue->created_by === (int) auth()->id())
            $('#submitDraftFromView').on('click', function() {
                Swal.fire({
                    title: 'ส่งเข้าระบบ?',
                    text: 'รายการจะเข้าคิวทีมงานตามข้อมูลที่บันทึกไว้ในร่าง',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'ส่งเข้าระบบ',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }
                    Swal.fire({
                        title: 'กำลังส่ง...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    $.ajax({
                        url: "{{ route('issue.submit', [$business, $issue->id]) }}",
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            from_stored: 1
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ส่งเข้าระบบสำเร็จ',
                                text: 'แจ้งปัญหาเรียบร้อยแล้ว',
                                confirmButtonText: 'ตกลง'
                            }).then(() => {
                                window.location.replace(res.redirect);
                            });
                        },
                        error: function(xhr) {
                            Swal.close();
                            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                                const first = Object.values(xhr.responseJSON.errors)[0][0];
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'ข้อมูลไม่ครบหรือไม่ถูกต้อง',
                                    text: first
                                });
                                return;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: xhr.responseJSON?.message ?? 'ไม่สามารถส่งข้อมูลได้'
                            });
                        }
                    });
                });
            });
        @endif
    </script>
@endsection