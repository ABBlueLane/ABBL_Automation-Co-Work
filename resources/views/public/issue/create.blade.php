@extends('layouts.public')
@section('content')
    <style>
        .active-dropzone {
            border: 2px solid #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.05);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
            transition: all 0.2s ease;
        }
    </style>
    <style>
        .dropzone::after {
            content: attr(data-text);
            display: block;
            text-align: center;
            color: #6c757d;
            margin-top: 10px;
        }
    </style>
    <div class="container py-4">
        {{-- Page Title --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <a href="{{ route('issue.index', $business) }}" class="btn btn-outline-dark btn-sm flex-shrink-0">
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
                                        <a href="{{ route('issue.index', $business) }}">Issue Management</a>
                                    </li>
                                    <li class="breadcrumb-item active">
                                        Issue Create
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm">

            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">
                    <i class="ri-bug-line me-1"></i>
                    แจ้งปัญหา
                </h4>
            </div>
            <div class="card-body">
                <form id="issueForm" action="#" method="POST">
                    @csrf
                    <input type="hidden" name="draft_issue_id" id="draftIssueId"
                        value="{{ ($issue?->status ?? null) === \App\Models\Issue::STATUS_DRAFT ? $issue->id : '' }}">
                    <div class="row g-3">
                        {{-- หัวเรื่อง --}}
                        <div class="col-lg-12">
                            <label class="form-label fw-semibold">
                                <i class="ri-edit-line me-1"></i>
                                เรื่อง <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control"
                                value="{{ old('title', $issue?->title ?? '') }}" placeholder="กรอกหัวข้อของปัญหา">
                        </div>
                        <div class="col-lg-12">
                            <label class="form-label fw-semibold">
                                <i class="ri-alarm-warning-line me-1"></i>
                                ความเร่งด่วน <span class="text-danger">*</span>
                            </label>
                            <select name="priority" class="form-select">
                                @foreach (\App\Models\Issue::getPriorityOptions() as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected((string) old('priority', $issue?->priority ?? \App\Models\Issue::PRIORITY_MEDIUM) === (string) $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($isIssueEmployee)
                            <div class="col-lg-12">
                                <label class="form-label fw-semibold">
                                    <i class="ri-project-line me-1"></i>
                                    โปรเจค <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal">(จำเป็นตอนส่งเข้าระบบ)</span>
                                </label>
                                <select name="issue_project_id" id="issue_project_id" class="form-select">
                                    <option value="">เลือกโปรเจค</option>
                                    @foreach ($issueProjects as $project)
                                        <option value="{{ $project->id }}"
                                            @selected((string) old('issue_project_id', $issue?->issue_project_id ?? '') === (string) $project->id)>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        {{-- Upload --}}
                        <div class="col-lg-12">

                            <label class="form-label fw-semibold">
                                <i class="ri-attachment-2 me-1"></i>
                                แนบไฟล์
                            </label>
                            <div class="dropzone border rounded-3 p-5 text-center bg-light" id="mediaDropzone"
                                tabindex="0">

                                <div class="dz-message">
                                    <i class="ri-upload-cloud-2-line fs-1 text-muted"></i>

                                    <p class="mt-2 mb-1">
                                        ลากไฟล์มาวาง หรือ
                                        <span id="browseTrigger" class="text-primary fw-semibold" style="cursor:pointer;">
                                            คลิกที่นี่
                                        </span>
                                        เพื่ออัปโหลด
                                    </p>

                                    <p class="mb-0">
                                        หรือกด <b>Ctrl + V</b> เพื่อวางภาพ
                                    </p>

                                    <small class="text-muted">
                                        รองรับภาพ วิดีโอ เอกสาร (PDF, Word, Excel, CSV) และไฟล์ข้อความ (MD, HTML, TXT, JSON, XML ฯลฯ)
                                    </small>
                                </div>
                            </div>
                        </div>
                        {{-- URL --}}
                        <div class="col-lg-12">
                            <label class="form-label fw-semibold">
                                <i class="ri-links-line me-1"></i>
                                แนบลิงก์ <span class="text-danger">*</span>
                            </label>
                            <input type="url" name="url" id="urlInput" class="form-control"
                                value="{{ old('url', $issue?->url ?? '') }}" placeholder="https://example.com">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="noUrlCheckbox">
                                <label class="form-check-label text-muted" for="noUrlCheckbox">
                                    ไม่มี URL สำหรับการแจ้งปัญหานี้
                                </label>
                            </div>
                        </div>
                        {{-- รายละเอียด --}}
                        <div class="col-lg-12">
                            <label class="form-label fw-semibold">
                                <i class="ri-file-text-line me-1"></i>
                                รายละเอียด
                            </label>
                            <textarea name="comment" rows="4" class="form-control" placeholder="อธิบายปัญหาที่พบ">{{ old('comment', $issue?->firstComment->comment ?? '') }}</textarea>
                        </div>
                        {{-- ปุ่ม --}}
                        <div class="col-lg-12 pt-2">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <a href="{{ route('issue.index', $business) }}" class="btn btn-light">
                                    ยกเลิก
                                </a>
                                <button type="button" class="btn btn-primary px-4" id="reviewBtn">
                                    <i class="ri-eye-line me-1"></i>
                                    รีวิว
                                </button>

                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="issueReviewModal" tabindex="-1" aria-labelledby="issueReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="issueReviewModalLabel">
                        <i class="ri-eye-line me-1"></i> รีวิวก่อนส่ง
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="issueReviewModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" id="reviewBackBtn" data-bs-dismiss="modal">
                        <i class="ri-arrow-left-line me-1"></i> กลับไปแก้ไข
                    </button>
                    <button type="button" class="btn btn-success" id="reviewSubmitBtn">
                        <i class="ri-send-plane-line me-1"></i> ส่งเข้าระบบ
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        Dropzone.autoDiscover = false;
        let uploadedFiles = [];
        let existingFiles = @json($issue?->firstComment->files ?? []);
        let originalComment = @json($issue?->firstComment->comment ?? '');
        const isDuplicateTemplate = @json($isDuplicateTemplate ?? false);
        const previewUrl = "{{ route('issue.preview', $business) }}";
        const storeSubmitUrl = "{{ route('issue.store.submit', $business) }}";
        const issueIndexBase = "{{ route('issue.index', $business) }}";
        let draftIssueId = $('#draftIssueId').val() || '';
        let pendingQueueAction = null;

        let myDropzone = new Dropzone("#mediaDropzone", {
            url: "{{ route('issue.upload', $business) }}",
            paramName: "file",
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            autoProcessQueue: false,
            uploadMultiple: false,
            parallelUploads: 10,
            maxFiles: 10,
            maxFilesize: 50,
            acceptedFiles: `
image/*,
video/mp4,video/webm,video/quicktime,
.pdf,.doc,.docx,.xls,.xlsx,.csv,
.md,.html,.htm,.txt,.json,.xml,.css,.js
`,
            addRemoveLinks: true,
            clickable: "#browseTrigger",
        });

        existingFiles.forEach(filePath => {

            let fileName = filePath.split('/').pop();
            let ext = fileName.split('.').pop().toLowerCase();

            let mockFile = {
                name: fileName,
                size: 0
            };

            myDropzone.emit("addedfile", mockFile);

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                myDropzone.emit("thumbnail", mockFile, "/storage/" + filePath);
            } else if (['mp4', 'webm', 'mov'].includes(ext)) {
                let video = document.createElement('video');
                video.src = "/storage/" + filePath;
                video.controls = true;
                video.style.width = "100%";
                video.style.borderRadius = "8px";

                let container = mockFile.previewElement.querySelector(".dz-image");
                if (container) {
                    container.innerHTML = "";
                    container.appendChild(video);
                }
            }

            myDropzone.emit("complete", mockFile);
            myDropzone.files.push(mockFile);
        });

        function addPlusButton() {

            myDropzone.files.forEach(file => {
                if (file.isAddButton && file.previewElement) {
                    file.previewElement.remove();
                }
            });

            myDropzone.files = myDropzone.files.filter(f => !f.isAddButton);

            let plusFile = {
                name: "add-more",
                size: 0,
                isAddButton: true
            };

            myDropzone.emit("addedfile", plusFile);

            let preview = plusFile.previewElement;

            preview.innerHTML = `
    <div style="
        display:flex;
        align-items:center;
        justify-content:center;
        height:120px;
        border:2px dashed #bbb;
        border-radius:12px;
        cursor:pointer;
        font-size:36px;
        color:#bbb;
        transition: all 0.2s;
    "
    onmouseover="this.style.borderColor='#0d6efd'; this.style.color='#0d6efd'"
    onmouseout="this.style.borderColor='#bbb'; this.style.color='#bbb'"
    >
        +
    </div>
`;

            preview.addEventListener("click", function() {
                document.querySelector("#browseTrigger").click();
            });

            myDropzone.files.push(plusFile);
        }

        if (existingFiles.length > 0) {
            addPlusButton();
        }

        myDropzone.on("addedfile", function(file) {

            if (file.isAddButton) return;

            let realFiles = myDropzone.files.filter(f => !f.isAddButton);

            if (realFiles.length >= 1) {
                addPlusButton();
            }
        });

        myDropzone.on("success", function(file, response) {
            uploadedFiles.push(response.path);
        });

        myDropzone.on("removedfile", function(file) {

            if (file.isAddButton) return;

            existingFiles = existingFiles.filter(path => {
                return path.split('/').pop() !== file.name;
            });

            uploadedFiles = uploadedFiles.filter(path => {
                return path.split('/').pop() !== file.name;
            });

            let realFiles = myDropzone.files.filter(f => !f.isAddButton);

            if (realFiles.length === 0) {

                myDropzone.files.forEach(f => {
                    if (f.isAddButton && f.previewElement) {
                        f.previewElement.remove();
                    }
                });

                myDropzone.files = myDropzone.files.filter(f => !f.isAddButton);

                document.querySelector("#mediaDropzone").classList.remove("dz-started");

            } else {
                addPlusButton();
            }
        });

        const dropzoneElement = document.getElementById('mediaDropzone');

        dropzoneElement.addEventListener('click', () => dropzoneElement.focus());

        dropzoneElement.addEventListener('focus', () => {
            dropzoneElement.classList.add('active-dropzone');
            dropzoneElement.setAttribute('data-text', '📥 วางรูปภาพที่นี่');
        });

        dropzoneElement.addEventListener('blur', () => {
            dropzoneElement.classList.remove('active-dropzone');
            dropzoneElement.setAttribute('data-text', 'คลิกหรือวางไฟล์');
        });

        dropzoneElement.addEventListener('paste', function(e) {
            const items = (e.clipboardData || window.clipboardData).items;

            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const file = items[i].getAsFile();
                    myDropzone.addFile(file);
                }
            }
        });

        $('#issueForm').on('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });

        $('#issueForm').on('submit', function(e) {
            e.preventDefault();
        });

        function buildSubmitPayload() {
            let currentComment = $('textarea[name="comment"]').val().trim();
            let allFiles = [...existingFiles, ...uploadedFiles];
            allFiles = allFiles.filter(f => f !== "add-more");
            const payload = {
                _token: "{{ csrf_token() }}",
                title: $('input[name="title"]').val().trim(),
                priority: $('select[name="priority"]').val() || '',
                comment: currentComment,
                url: $('#noUrlCheckbox').is(':checked') ? '' : ($('#urlInput').val() || ''),
                files: allFiles
            };
            if ($('select[name="issue_project_id"]').length) {
                payload.issue_project_id = $('select[name="issue_project_id"]').val() || '';
            }
            return payload;
        }

        function validateBeforeReview() {
            const title = $('input[name="title"]').val().trim();
            if (!title) {
                return 'กรุณากรอกหัวข้อ';
            }
            if (!$('select[name="priority"]').val()) {
                return 'กรุณาเลือกความเร่งด่วน';
            }
            if ($('select[name="issue_project_id"]').length && !$('select[name="issue_project_id"]').val()) {
                return 'กรุณาเลือกโปรเจค';
            }
            if (!$('#noUrlCheckbox').is(':checked')) {
                const u = ($('#urlInput').val() || '').trim();
                if (!u) {
                    return 'กรุณากรอกลิงก์ หรือเลือกไม่มี URL สำหรับการแจ้งปัญหานี้';
                }
            }
            return null;
        }

        function duplicateCommentBlocked() {
            let currentComment = $('textarea[name="comment"]').val().trim();
            return isDuplicateTemplate && originalComment && currentComment === originalComment;
        }

        function runLoadPreview() {
            $.ajax({
                url: previewUrl,
                method: "POST",
                data: buildSubmitPayload(),
                dataType: "html",
                success: function(html) {
                    Swal.close();
                    $('#issueReviewModalBody').html(html);
                    const modalEl = document.getElementById('issueReviewModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                },
                error: function(xhr) {
                    Swal.close();
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const first = Object.values(xhr.responseJSON.errors)[0][0];
                        Swal.fire({
                            icon: 'warning',
                            title: 'ข้อมูลไม่ถูกต้อง',
                            text: first
                        });
                        return;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: xhr.responseJSON?.message ?? 'ไม่สามารถโหลดรีวิวได้'
                    });
                }
            });
        }

        function runFinalSubmit() {
            const payload = buildSubmitPayload();
            const submitUrl = draftIssueId ?
                (issueIndexBase.replace(/\/$/, '') + '/' + draftIssueId + '/submit') :
                storeSubmitUrl;
            $.ajax({
                url: submitUrl,
                method: "POST",
                data: payload,
                success: function(res) {
                    Swal.close();
                    if (res.redirect) {
                        window.location.href = res.redirect;
                        return;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่ได้รับลิงก์เป้าหมายหลังส่ง'
                    });
                },
                error: function(xhr) {
                    Swal.close();
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const first = Object.values(xhr.responseJSON.errors)[0][0];
                        Swal.fire({
                            icon: 'warning',
                            title: 'ข้อมูลไม่ถูกต้อง',
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
        }

        $('#reviewBtn').on('click', function() {
            if (duplicateCommentBlocked()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้แก้ไขข้อมูล',
                    text: 'กรุณาแก้ไขข้อมูลก่อนส่ง (จากการ Duplicate)'
                });
                return;
            }
            const err = validateBeforeReview();
            if (err) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาตรวจสอบข้อมูล',
                    text: err
                });
                return;
            }
            Swal.fire({
                title: 'กำลังเตรียมรีวิว...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            pendingQueueAction = 'preview';
            if (myDropzone.getQueuedFiles().length > 0) {
                myDropzone.processQueue();
            } else {
                pendingQueueAction = null;
                runLoadPreview();
            }
        });

        $('#reviewSubmitBtn').on('click', function() {
            if (duplicateCommentBlocked()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้แก้ไขข้อมูล',
                    text: 'กรุณาแก้ไขข้อมูลก่อนส่ง (จากการ Duplicate)'
                });
                return;
            }
            const err = validateBeforeReview();
            if (err) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาตรวจสอบข้อมูล',
                    text: err
                });
                return;
            }
            Swal.fire({
                title: 'กำลังส่ง...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            pendingQueueAction = 'submit';
            if (myDropzone.getQueuedFiles().length > 0) {
                myDropzone.processQueue();
            } else {
                pendingQueueAction = null;
                runFinalSubmit();
            }
        });

        myDropzone.on('queuecomplete', function() {
            if (pendingQueueAction === 'preview') {
                pendingQueueAction = null;
                runLoadPreview();
            } else if (pendingQueueAction === 'submit') {
                pendingQueueAction = null;
                runFinalSubmit();
            }
        });

        myDropzone.on("error", function(file, message) {
            myDropzone.removeFile(file);
            pendingQueueAction = null;

            Swal.fire({
                icon: 'error',
                title: 'ไฟล์ไม่รองรับ',
                text: 'กรุณาอัปโหลดไฟล์ประเภทที่กำหนด'
            });

        });

        $('#noUrlCheckbox').change(function() {
            if ($(this).is(':checked')) {
                $('#urlInput').val('').prop('disabled', true);
            } else {
                $('#urlInput').prop('disabled', false);
            }
        });

        if ($('#noUrlCheckbox').is(':checked')) {
            $('#urlInput').val('').prop('disabled', true);
        }
    </script>
@endsection
