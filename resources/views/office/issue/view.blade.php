@extends('layouts.office')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0"><i class="ri-bug-line me-1"></i>
                    Issue Management System</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ officeBusinessRoute('issue.index') }}">Issue Management</a>
                        </li>
                        <li class="breadcrumb-item active">
                            Issue View
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            {{-- TITLE + DESCRIPTION --}}
            <div class="card mb-4">
                <div class="card-body">


                    {{-- TITLE --}}
<div class="d-flex align-items-start justify-content-between mb-3">
    <div>
        <h3 class="fw-bold mb-1 text-dark">
            <i class="ri-bug-line me-2 text-primary"></i>
            Issue #{{ $issue->issue_number }}
        </h3>

        <div class="text-muted small">
            {{ $issue->title }}
        </div>
    </div>

    <div>
        @php
            $statusMeta = \App\Models\Issue::getStatusMeta($issue->status);
        @endphp
        <span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
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
                    @php
                        $now = now();
                        $isOverdue =
                            $issue->due_at &&
                            $issue->status !== \App\Models\Issue::STATUS_DONE &&
                            $now->gt($issue->due_at);
                    @endphp
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
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                {!! $issue->getPriorityBadgeHtml() !!}
                                <select class="form-select form-select-sm w-auto" id="issuePrioritySelect">
                                    @foreach (\App\Models\Issue::getPriorityOptions() as $value => $label)
                                        <option value="{{ $value }}" @selected($issue->priority === $value)>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label class="text-muted small">วันที่มอบหมายล่าสุด</label>
                            <div>
                                {{ $issue->assigned_at ? $issue->assigned_at->format('d M Y H:i') : '-' }}
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label class="text-muted small">เริ่มงานตามแผน</label>
                            <div>
                                {{ $issue->planned_start_at ? $issue->planned_start_at->format('d M Y H:i') : '-' }}
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label class="text-muted small">กำหนดเสร็จ</label>
                            <div>
                                {{ $issue->due_at ? $issue->due_at->format('d M Y H:i') : '-' }}
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label class="text-muted small">วันเวลาปิดงาน</label>
                            <div>
                                {{ $issue->complete_at ? $issue->complete_at->format('d M Y H:i') : '-' }}
                            </div>
                        </div>

                        <div class="col-md-4 mt-3">
                            <label class="text-muted small">สถานะเวลา</label>
                            <div>
                                @if (!$issue->planned_start_at || !$issue->due_at)
                                    <span class="badge bg-secondary">ยังไม่กำหนดแผนเวลา</span>
                                @elseif($isOverdue)
                                    <span class="badge bg-danger">ค้างเกินกำหนด</span>
                                @else
                                    <span class="badge bg-success">ตามแผน</span>
                                @endif
                            </div>
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
                    @if ($issue->status !== \App\Models\Issue::STATUS_DONE)
                        {{-- ACCORDION ADD PROGRESS --}}
                        <div class="accordion mb-4" id="progressAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#addProgress">

                                        เพิ่มความคืบหน้า
                                    </button>
                                </h2>
                                <div id="addProgress" class="accordion-collapse collapse" data-bs-parent="#progressAccordion">
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
                                            <div class="d-flex flex-wrap gap-2">
                                                <button id="saveProgressBtn" class="btn btn-primary btn-sm" type="submit"
                                                    data-submit-action="save">
                                                    บันทึกความคืบหน้า
                                                </button>
                                                @if (in_array($issue->status, [\App\Models\Issue::STATUS_PENDING, \App\Models\Issue::STATUS_IN_PROGRESS, \App\Models\Issue::STATUS_CUSTOMER_REPLIED], true))
                                                    <button id="saveAndReviewBtn" class="btn btn-info btn-sm text-white"
                                                        type="submit" data-submit-action="save_and_review">
                                                        บันทึกความคืบหน้าและส่งตรวจ
                                                    </button>
                                                @endif
                                            </div>
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
                                    {{ $comment->created_at->diffForHumans() }}
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

@endsection
@section('script')
    <script>
        Fancybox.bind("[data-fancybox='gallery']");
        Dropzone.autoDiscover = false;
        let files = [];
        let uploadedFiles = [];
        let isSubmitting = false;
        let dz = null;
        let lastSubmitAction = "save";
        const $commentForm = $("#commentForm");
        const $saveButtons = $commentForm.find("button[type='submit']");

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

                    fetch("{{ officeBusinessRoute('issue.upload') }}", {
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
                            if (file) {
                                dz.addFile(file);
                            }
                        }
                    }
                });

            });
        }

        $saveButtons.on("click", function() {
            lastSubmitAction = $(this).data("submit-action") || "save";
        });

        $commentForm.submit(function(e) {
            e.preventDefault();

            if (isSubmitting) {
                return;
            }

            const submitterAction = e.originalEvent?.submitter?.dataset?.submitAction || lastSubmitAction || "save";

            let formData = new FormData();
            formData.append("comment", $("textarea[name=comment]").val());
            formData.append("submit_action", submitterAction);
            uploadedFiles.forEach(path => {
                formData.append("files[]", path);
            });

            isSubmitting = true;
            $saveButtons.prop("disabled", true);

            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            $.ajax({
                url: "{{ officeBusinessRoute('issue.comment.store', $issue->id) }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: submitterAction === "save_and_review" ?
                            "บันทึกความคืบหน้าและส่งตรวจแล้ว" : "บันทึกความคืบหน้าสำเร็จ",
                        timer: 1200,
                        showConfirmButton: false
                    });
                    $("textarea[name=comment]").val("");
                    files = [];
                    uploadedFiles = [];
                    if (dz) {
                        dz.removeAllFiles();
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr) {
                    let errorMessage = 'ไม่สามารถบันทึกได้';
                    const response = xhr.responseJSON || {};

                    if (response.errors && typeof response.errors === 'object') {
                        const firstKey = Object.keys(response.errors)[0];
                        const firstError = response.errors[firstKey];
                        if (Array.isArray(firstError) && firstError.length > 0) {
                            errorMessage = firstError[0];
                        }
                    } else if (response.message) {
                        errorMessage = response.message;
                    }

                    Swal.fire(
                        'ผิดพลาด',
                        errorMessage,
                        'error'
                    );
                },
                complete: function() {
                    isSubmitting = false;
                    $saveButtons.prop("disabled", false);
                }
            });
        });

        $("#issuePrioritySelect").change(function() {
            const priority = $(this).val();
            $.ajax({
                url: "{{ officeBusinessRoute('issue.priority', $issue->id) }}",
                method: "POST",
                data: {
                    priority: priority,
                    _token: "{{ csrf_token() }}"
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'อัปเดต SLA แล้ว',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                },
                error: function() {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถอัปเดต SLA ได้', 'error');
                }
            });
        });
    </script>
@endsection
