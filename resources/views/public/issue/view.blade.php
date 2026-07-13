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
    </style>
    <div class="container py-4">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <a href="{{ route('issue.index') }}" class="btn btn-outline-dark btn-sm flex-shrink-0">
                                        <i class="ri-arrow-left-line me-1"></i>
                                        ย้อนกลับ
                                    </a>
                                    <h4 class="mb-0">
                                        <i class="ri-bug-line me-1"></i>
                                        Issue Management System
                                    </h4>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12 text-lg-end text-start">

                                <ol class="breadcrumb m-0 ms-auto d-inline-flex">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('business.select') }}">เลือกธุรกิจ</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('issue.index') }}">Issue Management</a>
                                    </li>
                                    <li class="breadcrumb-item active">
                                        Issue View
                                    </li>
                                </ol>
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
                                <a href="{{ route('issue.create') }}?draft={{ $issue->id }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-edit-line me-1"></i> แก้ไขร่าง
                                </a>
                                <button type="button" class="btn btn-sm btn-success" id="submitDraftFromView">
                                    <i class="ri-send-plane-line me-1"></i> ส่งเข้าระบบ
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                {{-- TITLE + DESCRIPTION --}}
                <div class="card mb-4">
                    <div class="card-body">

                        <div class="row mb-3 align-items-start">

                            {{-- LEFT --}}
                            <div class="col-12 col-md">
                                @php
                                    $statusMeta = \App\Models\Issue::getStatusMeta($issue->status);
                                    $issueViewUrl = route('issue.view', $issue->id);
                                @endphp
                                <h3 class="fw-bold mb-1 text-dark">
    <i class="ri-bug-line me-2 text-primary"></i>Issue #{{ $issue->issue_number }}<button
        type="button"
        class="btn-copy-link-icon ms-2 align-middle"
        title="คัดลอกลิงก์" aria-label="คัดลอกลิงก์"
        onclick="copyText('{{ $issueViewUrl }}');">
        <i class="ri-links-line"></i>
    </button>
</h3>

<div class="text-muted small">
    {{ $issue->title }}
</div>
                                {{-- STATUS --}}
                                <div>
                                    <div class="mt-1">
                                        <span class="text-muted small me-1">สถานะ:</span>
                                        <span class="badge {{ $statusMeta['class'] }}">
                                            {{ $statusMeta['label'] }}
                                        </span>
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
                                </div>
                            </div>

                            {{-- RIGHT (status + buttons) --}}
                            <div class="col-12 col-md-auto">

                                <div
                                    class="d-flex flex-column flex-md-row align-items-md-center gap-2 justify-content-md-end">

                                    {{-- BUTTONS --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @if ($issue->status != \App\Models\Issue::STATUS_DONE && $issue->status !== \App\Models\Issue::STATUS_DRAFT)
                                            <button id="closeIssueBtn" class="btn btn-success btn-sm" type="button">
                                                <i class="ri-check-line me-1"></i> Approve and close issue
                                            </button>
                                        @endif
                                        <a href="{{ route('issue.index') }}" class="btn btn-primary btn-sm">
                                            <i class="ri-file-list-3-line me-1"></i> Home
                                        </a>
                                        <a href="{{ route('issue.duplicate', $issue->id) }}"
                                            class="btn btn-warning btn-sm btn-duplicate">
                                            <i class="ri-file-copy-line me-1"></i> Duplicate
                                        </a>
                                        <a href="{{ route('issue.create') }}" class="btn btn-primary btn-sm">
                                            <i class="ri-add-line me-1"></i> Create new
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <hr class="mt-2 mb-4">

                        {{-- DESCRIPTION --}}
                        <div class="mb-4">
                            {!! nl2br(e($issue->firstComment->comment ?? '-')) !!}
                        </div>

                        {{-- URL --}}
                        @if ($issue->url)
                            <div class="mb-4">
                                <label class="text-muted small d-block mb-1">
                                    <i class="ri-links-line me-1"></i> ลิงก์ที่เกี่ยวข้อง
                                </label>
                                <a href="{{ $issue->url }}" target="_blank" class="fw-semibold text-primary">
                                    {{ $issue->url }}
                                </a>
                            </div>
                        @endif

                        <hr>

                        {{-- ISSUE INFO --}}
                        <div class="row">
                            <div class="col-md-4">
                                <label class="text-muted small">ผู้สร้าง</label>
                                <div>
                                    {{ $issue->creator->full_name ?? '-' }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="text-muted small">ผู้รับผิดชอบ</label>
                                <div>
                                    {{ $issue->assignee->full_name ?? '-' }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="text-muted small">วันที่สร้าง</label>
                                <div>
                                    {{ $issue->created_at->format('d M Y H:i') }}
                                </div>
                            </div>

                            <div class="col-md-4 mt-3">
                                <label class="text-muted small">ความเร่งด่วน (SLA)</label>
                                <div>{!! $issue->getPriorityBadgeHtml() !!}</div>
                            </div>
                        </div>

                        {{-- FILES --}}
                        @php
                            $issueFiles = (array) (data_get($issue, 'firstComment.files') ?: []);
                        @endphp



                        @if (!empty($issueFiles))
                            <hr>

                            <label class="text-muted small mb-2">
                                ไฟล์แนบ
                            </label>

                            <div class="row">
                                @foreach ($issueFiles as $file)
                                    @include('public.issue._file_item', ['file' => $file])
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
                {{-- PROGRESS --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <b>ความคืบหน้า</b>
                    </div>
                    <div class="card-body">
                        @if ($issue->status !== \App\Models\Issue::STATUS_DONE && $issue->status !== \App\Models\Issue::STATUS_DRAFT)
                            {{-- ACCORDION ADD PROGRESS --}}
                            <div class="accordion mb-4" id="progressAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#addProgress">

                                            เพิ่มความคืบหน้า
                                        </button>
                                    </h2>
                                    <div id="addProgress" class="accordion-collapse collapse"
                                        data-bs-parent="#progressAccordion">
                                        <div class="accordion-body">
                                            <form id="commentForm">
                                                @csrf
                                                <div class="mb-3">
                                                    <textarea name="comment" class="form-control" rows="4" placeholder="พิมพ์ความคืบหน้า..."></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="dropzone border rounded-3 p-4 text-center bg-light"
                                                        id="dropzoneUpload" tabindex="0">

                                                        <div class="dz-message">
                                                            <i class="ri-upload-cloud-2-line fs-1 text-muted"></i>

                                                            <p class="mt-2 mb-1">
                                                                ลากไฟล์มาวาง หรือ
                                                                <span id="browseTrigger2" class="text-primary fw-semibold"
                                                                    style="cursor:pointer;">
                                                                    คลิกที่นี่
                                                                </span>
                                                                เพื่ออัปโหลด
                                                            </p>

                                                            <p class="mb-0">
                                                                หรือกด <b>Ctrl + V</b> เพื่อวางภาพ
                                                            </p>

                                                            <small class="text-muted">
                                                                รองรับภาพ วิดีโอ เอกสาร (PDF, Word, Excel, CSV) และไฟล์ข้อความ (MD, HTML, TXT ฯลฯ)
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button class="btn btn-primary btn-sm" type="submit">
                                                    บันทึกความคืบหน้า
                                                </button>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        {{-- COMMENT LIST --}}
                        @foreach ($comments as $comment)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="fw-bold">
                                        {{ $comment->user->full_name ?? $comment->user->name }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $comment->created_at->format('d/m/Y H:i') }}
                                        • {{ $comment->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="mb-2">
                                    {!! nl2br(e($comment->comment)) !!}
                                </div>
                                @php
                                    $files = (array) ($comment->files ?: []);
                                @endphp
                                @if (!empty($files))
                                    <div class="row">
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
                        @endforeach
                        {{-- PAGINATION --}}
                        <div class="mt-3">
                            {{ $comments->links() }}
                        </div>
                    </div>
                </div>
            </div>
            {{-- RIGHT SIDEBAR --}}

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

        const addProgressEl = document.getElementById('addProgress');
        if (addProgressEl) {
            addProgressEl.addEventListener('shown.bs.collapse', function() {

                if (dz) return;

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

                    clickable: "#browseTrigger2", // 🔥 ปุ่มคลิก
                });

                dz.on("addedfile", function(file) {

                    let formData = new FormData();
                    formData.append("file", file);
                    formData.append("_token", "{{ csrf_token() }}");

                    fetch("{{ route('issue.upload') }}", {
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

            });
        }
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
                url: "{{ route('issue.comment.store', $issue->id) }}",
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
                        url: "{{ route('issue.close', $issue->id) }}",
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
                        url: "{{ route('issue.submit', $issue->id) }}",
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