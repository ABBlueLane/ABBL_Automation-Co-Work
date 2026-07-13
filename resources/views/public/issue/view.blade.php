@extends('layouts.public')

@section('content')
    <style>
        .btn-copy-link-icon {
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 1.25rem;
            cursor: pointer;
            transition: color 0.15s ease, transform 0.15s ease;
        }
        .btn-copy-link-icon:hover {
            color: #4f46e5;
            transform: scale(1.1);
        }
        .btn-copy-link-icon:focus {
            outline: none;
        }

        /* --- Status timeline (kept from previous version) --- */
        .status-timeline-wrap {
            margin-top: 4px;
        }
        .status-timeline {
            position: relative;
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            padding: 0 4px;
        }
        .status-timeline::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }
        .status-timeline .st-fill {
            position: absolute;
            top: 8px;
            left: 8px;
            height: 3px;
            background: #4f46e5;
            z-index: 1;
            transition: width 0.3s ease;
        }
        .status-timeline .st-dot-wrap {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        .status-timeline .st-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        .status-timeline .st-dot.done {
            background: #4f46e5;
            border-color: #4f46e5;
        }
        .status-timeline .st-dot.current {
            background: #dc3545;
            border-color: #dc3545;
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.15);
        }
        .status-timeline .st-label {
            margin-top: 8px;
            font-size: 0.72rem;
            color: #9ca3af;
            text-align: center;
            white-space: nowrap;
        }
        .status-timeline .st-dot-wrap:first-child { align-items: flex-start; }
        .status-timeline .st-dot-wrap:last-child { align-items: flex-end; }

        /* --- New clean-card design --- */
        .review-card {
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .review-card.soft-bg {
            background: #f3f4f6;
            box-shadow: none;
        }

        .review-action-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: center;
            margin-bottom: 1.75rem;
        }

        .review-action-bar .btn {
            border-radius: 999px;
            padding: .45rem 1.1rem;
        }

        .review-title {
            font-weight: 700;
            font-size: 1.65rem;
            color: #1f2937;
            margin-bottom: .15rem;
        }

        .review-issue-number {
            color: #9ca3af;
            font-size: .85rem;
        }

        .info-field-label {
            color: #9ca3af;
            font-size: .75rem;
            margin-bottom: .25rem;
        }

        .avatar-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #e0e7ff;
            color: #4f46e5;
            font-size: .8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .link-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .75rem 1rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            color: #1f2937;
            text-decoration: none;
        }

        .link-row:hover {
            background: #f9fafb;
            color: #1f2937;
        }

        .link-row + .link-row {
            margin-top: .6rem;
        }

        .file-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: .85rem 1rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            height: 100%;
            text-decoration: none;
            color: #1f2937;
        }

        .file-card:hover {
            background: #f9fafb;
            color: #1f2937;
        }

        .file-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #374151;
            flex-shrink: 0;
        }

        .discussion-comment {
            display: flex;
            gap: .75rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f3f5;
        }

        .discussion-comment:last-of-type {
            border-bottom: none;
        }

        .comment-compose {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: .75rem 1rem;
        }

        .comment-compose textarea {
            border: none;
            resize: none;
            box-shadow: none !important;
            padding: 0;
        }

        .comment-compose textarea:focus {
            outline: none;
        }

        .back-to-edit-btn {
            border-radius: 999px;
            padding: .5rem 1.25rem;
        }

        .header-action-btn {
            border-radius: 12px;
            padding: .5rem 1.1rem;
            font-weight: 500;
        }
    </style>

    <div class="container py-4">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h4 class="mb-0">
                                        <i class="ri-bug-line me-1"></i>
                                        Review lssue Report
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">

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

                {{-- TITLE + ACTION BUTTONS --}}
                @php
                    $statusMeta = \App\Models\Issue::getStatusMeta($issue->status);
                    $issueViewUrl = route('issue.view', [$business, $issue->id]);
                @endphp
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <div class="review-title">
                            {{ $issue->issue_number }}
                            <button type="button" class="btn-copy-link-icon ms-1 align-middle" title="คัดลอกลิงก์"
                                aria-label="คัดลอกลิงก์" onclick="copyText('{{ $issueViewUrl }}');">
                                <i class="ri-links-line"></i>
                            </button>
                        </div>
                        <div class="review-issue-number">
                            {{ $issue->title }}
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @if ($issue->status != \App\Models\Issue::STATUS_DONE && $issue->status !== \App\Models\Issue::STATUS_DRAFT)
                            <button id="closeIssueBtn" class="btn btn-primary btn-sm header-action-btn" type="button">
                                <i class="ri-check-line me-1"></i> Approve and close issue
                            </button>
                        @endif
                        <a href="{{ route('issue.index', $business) }}" class="btn btn-light btn-sm border header-action-btn">
                            <i class="ri-home-4-line me-1"></i> Home
                        </a>
                        <a href="{{ route('issue.duplicate', [$business, $issue->id]) }}"
                            class="btn btn-light btn-sm border header-action-btn btn-duplicate">
                            <i class="ri-file-copy-line me-1"></i> Duplicate
                        </a>
                        <a href="{{ route('issue.create', $business) }}" class="btn btn-light btn-sm border header-action-btn">
                            <i class="ri-add-line me-1"></i> Create New
                        </a>
                    </div>
                </div>

                {{-- SINGLE MERGED SHEET --}}
                <div class="review-card mb-4">
                    <div class="card-body p-4">

                        {{-- หัวข้อปัญหา --}}
                        <div class="fw-semibold mb-3">
                            <i class="ri-information-line me-1 text-primary"></i> หัวข้อปัญหา
                        </div>

                        <div class="row g-4">
                            <div class="col-6 col-md-3">
                                <div class="info-field-label">โปรเจค</div>
                                <div>
                                    <i class="ri-building-2-line me-1 text-muted"></i>
                                    {{ $business->business_name ?? '-' }}
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-field-label">ระดับความเร่งด่วน</div>
                                <div>{!! $issue->getPriorityBadgeHtml() !!}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-field-label">เนื่องวันที่</div>
                                <div>{{ $issue->created_at->format('d M Y • H:i') }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-field-label">ผู้รับผิดชอบ</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar-circle">
                                        {{ mb_substr($issue->assignee->full_name ?? '-', 0, 1) }}
                                    </span>
                                    {{ $issue->assignee->full_name ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div>
                                <span class="text-muted small me-1">ผู้สร้าง:</span>
                                <span class="fw-semibold">{{ $issue->creator->full_name ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-muted small me-1">สถานะ:</span>
                                <span class="badge {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </div>
                        </div>

                        @if ($issue->status !== \App\Models\Issue::STATUS_DRAFT)
                            @php
                                $statusSteps = [
                                    \App\Models\Issue::STATUS_PENDING => 'รอรีวิว',
                                    \App\Models\Issue::STATUS_IN_PROGRESS => 'กำลังดำเนินการ',
                                    \App\Models\Issue::STATUS_CUSTOMER_REPLIED => 'รอลูกค้าตอบกลับ',
                                    \App\Models\Issue::STATUS_DONE => 'ปิดเคสแล้ว',
                                ];
                                $stepKeys = array_keys($statusSteps);
                                $currentIndex = array_search($issue->status, $stepKeys);
                                $currentIndex = $currentIndex === false ? 0 : $currentIndex;
                                $totalSteps = count($stepKeys);
                                $fillPercent = $totalSteps > 1 ? ($currentIndex / ($totalSteps - 1)) * 100 : 0;
                            @endphp
                            <div class="status-timeline-wrap" style="max-width: 480px;">
                                <div class="status-timeline">
                                    <div class="st-fill" style="width: {{ $fillPercent }}%;"></div>
                                    @foreach ($statusSteps as $key => $label)
                                        @php
                                            $idx = $loop->index;
                                            $dotClass = $idx < $currentIndex ? 'done' : ($idx === $currentIndex ? 'current' : '');
                                        @endphp
                                        <div class="st-dot-wrap">
                                            <div class="st-dot {{ $dotClass }}"></div>
                                            <div class="st-label">{{ $label }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <hr class="my-4">

                        {{-- รายละเอียด --}}
                        <div class="fw-semibold mb-2">
                            <i class="ri-file-text-line me-1 text-primary"></i> รายละเอียด
                        </div>
                        <div class="p-3 rounded-3" style="background:#f3f4f6;">
                            {!! nl2br(e($issue->firstComment->comment ?? '-')) !!}
                        </div>

                        <hr class="my-4">

                        {{-- แนบลิงก์ + แนบไฟล์ --}}
                        @php
                            $issueFiles = (array) (data_get($issue, 'firstComment.files') ?: []);
                        @endphp
                        <div class="row g-4">
                            <div class="col-lg-5">
                                <div class="p-3 rounded-4 h-100" style="background:#f3f4f6;">
                                    <div class="fw-semibold mb-3">
                                        <i class="ri-links-line me-1 text-primary"></i> แนบลิงก์
                                    </div>
                                    @if ($issue->url)
                                        <a href="{{ $issue->url }}" target="_blank" class="link-row">
                                            <span class="text-truncate">{{ $issue->url }}</span>
                                            <i class="ri-external-link-line text-muted"></i>
                                        </a>
                                    @else
                                        <div class="text-muted small">ไม่มีลิงก์ที่เกี่ยวข้อง</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="p-3 rounded-4 h-100" style="background:#f3f4f6;">
                                    <div class="fw-semibold mb-3">
                                        <i class="ri-attachment-2 me-1 text-primary"></i> แนบไฟล์
                                    </div>
                                    @if (!empty($issueFiles))
                                        <div class="row g-2">
                                            @foreach ($issueFiles as $file)
                                                @include('public.issue._file_icon_card', ['file' => $file])
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted small">ไม่มีไฟล์แนบ</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DISCUSSION / PROGRESS (separate sheet) --}}
                <div class="review-card mb-4">
                    <div class="card-body p-4">

                        <div class="fw-semibold mb-3">
                            <i class="ri-chat-3-line me-1 text-primary"></i> Discussion
                        </div>

                        @forelse ($comments as $comment)
                            <div class="discussion-comment">
                                <span class="avatar-circle" style="width:36px;height:36px;font-size:.95rem;">
                                    {{ mb_substr($comment->user->full_name ?? $comment->user->name ?? '-', 0, 1) }}
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
                                        <div class="row g-2 mt-1">
                                            @foreach ($files as $file)
                                                @include('public.issue._file_icon_card', ['file' => $file])
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
                            <form id="commentForm" class="mt-3">
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

                                    <div class="d-flex align-items-center justify-content-between mt-2">
                                        <button type="button" id="commentAttachToggle"
                                            class="btn btn-light border rounded-circle d-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;" title="แนบไฟล์หรือรูปภาพ">
                                            <i class="ri-attachment-2"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm rounded-pill px-4" type="submit">
                                            <i class="ri-send-plane-fill me-1"></i> Send
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                <a href="{{ ($issue->status === \App\Models\Issue::STATUS_DRAFT && (int) $issue->created_by === (int) auth()->id()) ? route('issue.create', $business) . '?draft=' . $issue->id : route('issue.index', $business) }}"
                    class="btn btn-light border back-to-edit-btn">
                    <i class="ri-arrow-left-line me-1"></i> Back to Edit
                </a>

            </div>
        </div>
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

            /* =========================
               🔥 Ctrl + V Paste
            ========================= */
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
            formData.append(
                "comment",
                $("textarea[name=comment]").val()
            );
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
                    // reset form
                    $("textarea[name=comment]").val("");
                    files = [];
                    if (dz) {
                        dz.removeAllFiles();
                    }
                    // reload เฉพาะ comment section
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                },
                error: function() {

                    Swal.fire(
                        'ผิดพลาด',
                        'ไม่สามารถบันทึกได้',
                        'error'
                    );
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