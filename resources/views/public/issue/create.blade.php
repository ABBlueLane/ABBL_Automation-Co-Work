@extends('layouts.public')
@section('navbar_container', 'container')
@section('content')
    <style>
        :root {
            --im-primary: #4f46e5;
            --im-primary-dark: #3730a3;
            --im-primary-soft: #eef0fe;
            --im-border: #e5e7eb;
            --im-text: #1f2937;
            --im-text-muted: #6b7280;
            --im-bg-card: #ffffff;
            --im-radius-lg: 16px;
            --im-radius-md: 12px;
            --im-radius-sm: 8px;
            --im-shadow-sm: 0 2px 8px rgba(17, 24, 39, 0.06);
            --im-shadow-md: 0 8px 24px rgba(17, 24, 39, 0.08);
            --im-space-1: 6px;
            --im-space-2: 12px;
            --im-space-3: 20px;
            --im-space-4: 28px;
        }

        /* ---------- Page header card ---------- */
        .container.py-4 > .row:first-child .card {
            border: 1px solid var(--im-border);
            border-radius: var(--im-radius-lg);
            box-shadow: var(--im-shadow-sm);
        }

        .container.py-4 h4.mb-0 {
            font-weight: 700;
            color: var(--im-text);
            letter-spacing: -0.01em;
        }

        .container.py-4 .breadcrumb-item a {
            color: var(--im-text-muted);
            text-decoration: none;
            font-weight: 500;
        }
        .container.py-4 .breadcrumb-item a:hover { color: var(--im-primary); }
        .container.py-4 .breadcrumb-item.active { color: var(--im-primary); font-weight: 600; }

        h4.fw-bold.mb-3 {
            color: var(--im-text);
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-top: var(--im-space-3);
        }

        /* ---------- Dropzone ---------- */
        .active-dropzone {
            border: 2px solid var(--im-primary) !important;
            background-color: var(--im-primary-soft);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
            transition: all 0.2s ease;
        }

        .dropzone {
            border-radius: var(--im-radius-md) !important;
            border: 1.5px dashed #cbd5e1 !important;
            background: #fafafa !important;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .dropzone:hover {
            border-color: var(--im-primary) !important;
            background: var(--im-primary-soft) !important;
        }
        .dropzone::after {
            content: attr(data-text);
            display: block;
            text-align: center;
            color: var(--im-text-muted);
            margin-top: var(--im-space-1);
            font-size: 0.8rem;
        }
        .dz-message i {
            color: var(--im-primary);
        }

        /* ---------- Priority dots ---------- */
        .priority-picker {
            display: flex;
            align-items: center;
            gap: var(--im-space-3);
        }

        .priority-dot-wrap {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
        }

        .priority-dot-wrap .btn-check {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .priority-dot {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            display: inline-block;
            border: 3px solid transparent;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .06);
            transition: all .18s ease;
        }

        .priority-dot:hover {
            transform: scale(1.1);
        }

        .btn-check:checked+.priority-dot {
            border-color: var(--im-text);
            box-shadow: 0 0 0 3px #fff inset, 0 0 0 5px rgba(31, 41, 55, .14);
            transform: scale(1.06);
        }

        /* ---------- Field icon labels --- */
        .field-icon-label {
            font-size: 1.05rem;
            color: var(--im-primary);
            margin-bottom: var(--im-space-1);
            display: inline-flex;
            align-items: center;
        }

        /* ---------- Comment box inline attach hint ---------- */
        .comment-attach-hint {
            position: absolute;
            right: 14px;
            bottom: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--im-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(79, 70, 229, .3);
            transition: transform 0.15s ease, background 0.15s ease;
        }

        .comment-attach-hint:hover {
            background: var(--im-primary-dark);
            transform: scale(1.06);
        }

        /* ---------- Icon-only action buttons ---------- */
        .btn-icon-action {
            width: 48px;
            height: 48px;
            border-radius: var(--im-radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: transform 0.15s ease;
        }
        .btn-icon-action:hover {
            transform: translateY(-2px);
        }
        #reviewBtn.btn-primary {
            background: var(--im-primary);
            border-color: var(--im-primary);
            box-shadow: var(--im-shadow-sm);
        }
        #reviewBtn.btn-primary:hover {
            background: var(--im-primary-dark);
            border-color: var(--im-primary-dark);
        }

        /* ---------- Consistent field framing ---------- */
        .field-box {
            background: var(--im-bg-card);
            border: 1px solid var(--im-border);
            border-radius: var(--im-radius-md);
            padding: var(--im-space-3) var(--im-space-4);
            height: 100%;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .field-box:focus-within {
            border-color: var(--im-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .field-box .form-control,
        .field-box .form-select {
            border-radius: var(--im-radius-sm);
            border-color: var(--im-border);
            padding: 10px 14px;
        }
        .field-box .form-control:focus,
        .field-box .form-select:focus {
            border-color: var(--im-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* ---------- Step status indicator ---------- */
        #issueStepper {
            padding: var(--im-space-3) 0;
            margin-bottom: var(--im-space-3) !important;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--im-primary-soft);
            color: var(--im-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all .2s ease;
        }

        .step-circle.active {
            background: var(--im-primary);
            color: #fff;
            box-shadow: 0 0 0 5px rgba(79, 70, 229, .14);
        }

        .step-circle.done {
            background: #10b981;
            color: #fff;
            box-shadow: 0 0 0 5px rgba(16, 185, 129, .14);
        }

        .step-item.done .step-label,
        .step-item.active .step-label {
            color: var(--im-primary);
            font-weight: 700;
        }

        .step-arrow.done {
            color: #10b981;
        }

        .wizard-step-actions {
            border-top: 1px solid var(--im-border);
            margin-top: var(--im-space-3);
            padding-top: var(--im-space-3);
        }

        .success-step-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #ecfdf5;
            color: #10b981;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: var(--im-space-2);
        }

        .ims-number-badge {
            display: inline-block;
            background: var(--im-primary-soft);
            color: var(--im-primary-dark);
            font-size: 1.25rem;
            font-weight: 800;
            padding: 10px 20px;
            border-radius: var(--im-radius-sm);
            letter-spacing: 0.02em;
        }

        .review-field-box {
            background: var(--im-bg-card);
            border: 1px solid var(--im-border);
            border-radius: var(--im-radius-md);
            padding: var(--im-space-3) var(--im-space-4);
            height: 100%;
        }

        .review-field-label {
            font-size: .85rem;
            font-weight: 600;
            color: var(--im-text-muted);
            margin-bottom: 6px;
        }

        .review-field-value {
            color: var(--im-text);
            font-weight: 500;
            word-break: break-word;
        }

        .review-priority-dot {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 3px solid var(--im-text);
            display: inline-block;
        }

        .step-label {
            text-align: center;
            font-size: .8rem;
            font-weight: 600;
            color: var(--im-text-muted);
            margin-top: var(--im-space-1);
            white-space: nowrap;
        }

        .step-arrow {
            color: #cbd5e1;
            font-size: 1.3rem;
            margin: 0 var(--im-space-3);
            padding-bottom: 1.8rem;
        }

        /* ---------- Main form card ---------- */
        .card.shadow-sm {
            border: 1px solid var(--im-border);
            border-radius: var(--im-radius-lg);
            box-shadow: var(--im-shadow-md) !important;
        }
        .card.shadow-sm .card-body {
            padding: var(--im-space-4);
        }

        /* ---------- Modal polish ---------- */
        #reviewSubmitBtn {
            border-radius: var(--im-radius-sm);
            font-weight: 600;
            padding: 10px 24px;
        }
        #reviewBackBtn {
            border-radius: var(--im-radius-sm);
            font-weight: 500;
        }
        #reviewBtn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
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

        <h4 class="fw-bold mb-3">
            <i class="ri-file-list-3-line me-1"></i>
            รายงานปัญหา
        </h4>

        <div class="d-flex align-items-start justify-content-center mb-4" id="issueStepper">
            <div class="step-item" data-step="1">
                <div class="step-circle active">1</div>
                <div class="step-label">กรอกข้อมูล</div>
            </div>
            <div class="step-arrow">→</div>
            <div class="step-item" data-step="2">
                <div class="step-circle">2</div>
                <div class="step-label">สรุปข้อมูล</div>
            </div>
            <div class="step-arrow">→</div>
            <div class="step-item" data-step="3">
                <div class="step-circle">3</div>
                <div class="step-label">บันทึกสำเร็จ</div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                {{-- Step 1: ฟอร์มกรอกข้อมูล --}}
                <div id="stepPanel1" class="wizard-step">
                <form id="issueForm" action="#" method="POST">
                    @csrf
                    <input type="hidden" name="draft_issue_id" id="draftIssueId"
                        value="{{ ($issue?->status ?? null) === \App\Models\Issue::STATUS_DRAFT ? $issue->id : '' }}">

                    <div class="row g-3 align-items-stretch">
                        {{-- หัวเรื่อง + โปรเจค --}}
                        <div class="col-lg-7">
                            <div class="field-box">
                                <span class="field-icon-label mb-2"><i class="ri-edit-line"></i></span>
                                <span class="fs-6 fw-semibold text-dark">ปัญหา <span class="text-danger">*</span></span>
                                <input type="text" name="title" class="form-control mt-1"
                                    value="{{ old('title', $issue?->title ?? '') }}" placeholder="กรอกหัวข้อของปัญหา">

                                <div class="mt-3">
                                    <span class="field-icon-label mb-2"><i class="ri-building-2-line"></i></span>
                                    <span class="fs-6 fw-semibold text-dark">โปรเจค <span class="text-danger">*</span></span>
                                    @php
                                        $currentBusiness = \App\Models\Business::find($business);
                                        $selectedIssueProjectId = old(
                                            'issue_project_id',
                                            $issue?->issue_project_id
                                                ?? optional($issueProjects->firstWhere('name', $currentBusiness?->business_name))->id
                                                ?? ''
                                        );
                                    @endphp
                                    <select name="issue_project_id" id="issue_project_id" class="form-select mt-1">
                                        <option value="">เลือกโปรเจค (บริษัท)</option>
                                        @foreach ($issueProjects as $project)
                                            <option value="{{ $project->id }}"
                                                @selected((string) $selectedIssueProjectId === (string) $project->id)>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="field-box d-flex flex-column align-items-center justify-content-center text-center">
                                <small class="text-muted mb-2">ระดับความเร่งด่วน</small>
                                <div class="priority-picker">
                                    <i class="ri-alarm-warning-line fs-3 text-muted"></i>
                                    @php
                                        $priorityColors = [
                                            \App\Models\Issue::PRIORITY_HIGH ?? 'high' => '#28a745',
                                            \App\Models\Issue::PRIORITY_MEDIUM ?? 'medium' => '#ffc107',
                                            \App\Models\Issue::PRIORITY_LOW ?? 'low' => '#dc3545',
                                        ];
                                        $priorityCaptionsByColor = [
                                            '#dc3545' => 'มาก',
                                            '#ffc107' => 'กลาง',
                                            '#28a745' => 'น้อย',
                                        ];
                                        $selectedPriority = (string) old('priority', $issue?->priority ?? \App\Models\Issue::PRIORITY_MEDIUM);
                                    @endphp
                                    @foreach (\App\Models\Issue::getPriorityOptions() as $value => $label)
                                        <span class="priority-dot-wrap d-flex flex-column align-items-center">
                                            <input class="btn-check" type="radio" name="priority" id="priority_{{ $value }}"
                                                value="{{ $value }}" @checked($selectedPriority === (string) $value)>
                                            <label class="priority-dot" for="priority_{{ $value }}" title="{{ $label }}"
                                                style="background-color: {{ $priorityColors[$value] ?? '#6c757d' }};"></label>
                                            <small class="text-muted mt-1" style="font-size: .7rem;">
                                                {{ $priorityCaptionsByColor[$priorityColors[$value] ?? ''] ?? '' }}
                                            </small>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- รายละเอียด --}}
                        <div class="col-lg-12">
                            <div class="field-box">
                                <span class="field-icon-label mb-2"><i class="ri-file-text-line"></i></span>
                                <span class="fs-6 fw-semibold text-dark">รายละเอียด <span class="text-danger">*</span></span>
                                <div class="position-relative mt-1">
                                    <textarea name="comment" id="commentTextarea" rows="4" class="form-control"
                                        placeholder="อธิบายปัญหาที่พบ">{{ old('comment', $issue?->firstComment->comment ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- แนบไฟล์ + ลิงก์ --}}
                        <div class="col-lg-7">
                            <div class="field-box">
                                <span class="field-icon-label mb-2"><i class="ri-attachment-2"></i></span>
                                <span class="fs-6 fw-semibold text-dark">แนบไฟล์</span>
                                <div class="dropzone border rounded-3 p-4 text-center bg-light mt-1" id="mediaDropzone"
                                    tabindex="0">
                                    <div class="dz-message">
                                        <i class="ri-upload-cloud-2-line fs-2 text-muted"></i>
                                        <p class="mt-2 mb-1 small">
                                            ลากไฟล์มาวาง หรือ
                                            <span id="browseTrigger" class="text-primary fw-semibold" style="cursor:pointer;">
                                                คลิกที่นี่
                                            </span>
                                            หรือ <b>Ctrl + V</b> เพื่อวางภาพ
                                        </p>
                                        <small class="text-muted d-block">
                                            รองรับภาพ วิดีโอ เอกสาร (PDF, Word, Excel, CSV) และไฟล์ข้อความ
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="field-box">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="field-icon-label mb-0">
                                        <i class="ri-links-line me-1"></i>
                                        <span class="fs-6 fw-semibold text-dark">แนบลิงก์ <span class="text-danger">*</span></span>
                                    </span>
                                </div>
                                <input type="url" name="url" id="urlInput" class="form-control"
                                    value="{{ old('url', $issue?->url ?? '') }}" placeholder="https://example.com">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="noUrlCheckbox">
                                    <label class="form-check-label text-muted small" for="noUrlCheckbox">
                                        ไม่มี URL สำหรับการแจ้งปัญหานี้
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- ปุ่ม --}}
                        <div class="col-lg-12 pt-2">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <a href="{{ route('issue.index', $business) }}" class="btn btn-outline-danger btn-icon-action"
                                    title="ยกเลิก">
                                    <i class="ri-delete-bin-line"></i>
                                </a>
                                <button type="button" class="btn btn-primary btn-icon-action" id="reviewBtn"
                                    title="ถัดไป">
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                </div>

                {{-- Step 2: สรุปข้อมูล + ปุ่มบันทึก --}}
                <div id="stepPanel2" class="wizard-step d-none">
                    <div class="mb-3">
                        <h5 class="fw-bold mb-1">
                            <i class="ri-eye-line me-1 text-primary"></i>
                            ตรวจสอบข้อมูลก่อนบันทึก
                        </h5>
                        <p class="text-muted small mb-0">กรุณาตรวจสอบความถูกต้องก่อนกดบันทึกเข้าระบบ</p>
                    </div>
                    <div id="issueReviewBody"></div>
                    <div class="wizard-step-actions d-flex flex-wrap justify-content-between gap-2">
                        <button type="button" class="btn btn-light" id="reviewBackBtn">
                            <i class="ri-arrow-left-line me-1"></i> กลับไปแก้ไข
                        </button>
                        <button type="button" class="btn btn-success" id="reviewSubmitBtn">
                            <i class="ri-save-line me-1"></i> บันทึกเข้าระบบ
                        </button>
                    </div>
                </div>

                {{-- Step 3: ผลการบันทึก + เลข IMS --}}
                <div id="stepPanel3" class="wizard-step d-none">
                    <div class="text-center py-3 mb-3">
                        <div class="success-step-icon">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">บันทึกข้อมูลสำเร็จ</h4>
                        <p class="text-muted mb-2">รายการของคุณถูกส่งเข้าระบบเรียบร้อยแล้ว</p>
                        <div class="mb-1 text-muted small">เลข IMS</div>
                        <div class="ims-number-badge" id="savedIssueNumber">-</div>
                    </div>
                    <div id="step3DetailBody"></div>
                    <div class="wizard-step-actions d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('issue.index', $business) }}" class="btn btn-outline-secondary">
                            <i class="ri-list-check me-1"></i> กลับหน้ารายการ
                        </a>
                        <a href="#" class="btn btn-primary" id="viewIssueBtn">
                            <i class="ri-external-link-line me-1"></i> ดูรายละเอียด
                        </a>
                    </div>
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
        let currentStep = 1;

        function setActiveStep(step) {
            currentStep = step;
            const arrows = document.querySelectorAll('#issueStepper .step-arrow');
            document.querySelectorAll('#issueStepper .step-item').forEach(function(item) {
                const stepNum = parseInt(item.dataset.step, 10);
                const circle = item.querySelector('.step-circle');
                circle.classList.remove('active', 'done');
                item.classList.remove('active', 'done');

                if (stepNum < step) {
                    circle.classList.add('done');
                    item.classList.add('done');
                    circle.innerHTML = '<i class="ri-check-line"></i>';
                } else if (stepNum === step) {
                    circle.classList.add('active');
                    item.classList.add('active');
                    circle.textContent = stepNum;
                } else {
                    circle.textContent = stepNum;
                }
            });

            arrows.forEach(function(arrow, index) {
                arrow.classList.toggle('done', index < step - 1);
            });
        }

        function goToStep(step) {
            document.querySelectorAll('.wizard-step').forEach(function(panel) {
                panel.classList.add('d-none');
            });
            document.getElementById('stepPanel' + step).classList.remove('d-none');
            setActiveStep(step);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

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
            clickable: true,
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

       

        dropzoneElement.addEventListener('blur', () => {
            dropzoneElement.classList.remove('active-dropzone');
            dropzoneElement.setAttribute('data-text', 'คลิกหรือวางไฟล์');
        });

        function handlePasteEvent(e) {
            const items = (e.clipboardData || window.clipboardData).items;

            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const file = items[i].getAsFile();
                    myDropzone.addFile(file);
                }
            }
        }

        dropzoneElement.addEventListener('paste', handlePasteEvent);

        // อนุญาตให้วางรูปภาพขณะโฟกัสอยู่ในช่องรายละเอียดได้ด้วย
        document.getElementById('commentTextarea').addEventListener('paste', handlePasteEvent);

        // ไอคอนแนบรูปภาพในกล่องรายละเอียด -> เปิดตัวเลือกไฟล์ของ dropzone
        

        $('#issueForm').on('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });

        $('#issueForm').on('submit', function(e) {
            e.preventDefault();
        });

        function buildSubmitPayload() {
            let currentComment = $('#commentTextarea').val().trim();
            let allFiles = [...existingFiles, ...uploadedFiles];
            allFiles = allFiles.filter(f => f !== "add-more");
            const payload = {
                _token: "{{ csrf_token() }}",
                title: $('input[name="title"]').val().trim(),
                priority: $('input[name="priority"]:checked').val() || '',
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
            if (!$('input[name="priority"]:checked').val()) {
                return 'กรุณาเลือกความเร่งด่วน';
            }
            if ($('select[name="issue_project_id"]').length && !$('select[name="issue_project_id"]').val()) {
                return 'กรุณาเลือกโปรเจค';
            }
            const comment = $('#commentTextarea').val().trim();
            if (!comment) {
                return 'กรุณากรอกรายละเอียด';
            }
            if (!$('#noUrlCheckbox').is(':checked')) {
                const u = ($('#urlInput').val() || '').trim();
                if (!u) {
                    return 'กรุณากรอกลิงก์ หรือเลือกไม่มี URL สำหรับการแจ้งปัญหานี้';
                }
            }
            return null;
        }

        function updateReviewButtonState() {
            const isComplete = validateBeforeReview() === null && !duplicateCommentBlocked();
            $('#reviewBtn').prop('disabled', !isComplete).toggleClass('disabled', !isComplete);
        }

        function duplicateCommentBlocked() {
            let currentComment = $('#commentTextarea').val().trim();
            return isDuplicateTemplate && originalComment && currentComment === originalComment;
        }

        function escapeHtml(text) {
            return $('<div>').text(text ?? '').html();
        }

        function getPriorityReviewMeta() {
            const value = $('input[name="priority"]:checked').val();
            const map = {
                high: { color: '#28a745', label: 'น้อย' },
                medium: { color: '#ffc107', label: 'กลาง' },
                low: { color: '#dc3545', label: 'มาก' },
            };
            return map[value] || { color: '#6c757d', label: '-' };
        }

        function getReviewFileNames() {
            return myDropzone.files
                .filter(function(file) { return !file.isAddButton; })
                .map(function(file) { return file.name; });
        }

        function renderFormReviewSummary() {
            const title = $('input[name="title"]').val().trim();
            const projectName = $('#issue_project_id option:selected').text().trim();
            const comment = $('#commentTextarea').val().trim();
            const noUrl = $('#noUrlCheckbox').is(':checked');
            const url = noUrl ? '' : ($('#urlInput').val() || '').trim();
            const priority = getPriorityReviewMeta();
            const files = getReviewFileNames();

            let filesHtml = '<div class="review-field-value text-muted">-</div>';
            if (files.length > 0) {
                filesHtml = '<ul class="list-unstyled mb-0 review-field-value">' + files.map(function(name) {
                    return '<li><i class="ri-file-line me-1"></i>' + escapeHtml(name) + '</li>';
                }).join('') + '</ul>';
            }

            let urlHtml = '<span class="text-muted">ไม่มี URL สำหรับการแจ้งปัญหานี้</span>';
            if (!noUrl && url) {
                urlHtml = '<a href="' + escapeHtml(url) + '" target="_blank" class="text-primary fw-semibold">' + escapeHtml(url) + '</a>';
            }

            const html = `
                <div class="review-summary">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="review-field-box">
                                <div class="review-field-label"><i class="ri-edit-line me-1"></i> ปัญหา</div>
                                <div class="review-field-value">${escapeHtml(title)}</div>
                                <div class="review-field-label mt-3"><i class="ri-building-2-line me-1"></i> โปรเจค</div>
                                <div class="review-field-value">${escapeHtml(projectName || '-')}</div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="review-field-box text-center">
                                <div class="review-field-label mb-2">ระดับความเร่งด่วน</div>
                                <div class="d-inline-flex flex-column align-items-center">
                                    <span class="review-priority-dot" style="background-color: ${priority.color};"></span>
                                    <small class="text-muted mt-1">${escapeHtml(priority.label)}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="review-field-box">
                                <div class="review-field-label"><i class="ri-file-text-line me-1"></i> รายละเอียด</div>
                                <div class="review-field-value">${escapeHtml(comment).replace(/\n/g, '<br>')}</div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="review-field-box">
                                <div class="review-field-label"><i class="ri-attachment-2 me-1"></i> แนบไฟล์</div>
                                ${filesHtml}
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="review-field-box">
                                <div class="review-field-label"><i class="ri-links-line me-1"></i> แนบลิงก์</div>
                                <div class="review-field-value">${urlHtml}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#issueReviewBody').html(html);
            goToStep(2);
        }

        function runLoadPreview() {
            Swal.close();
            renderFormReviewSummary();
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
                    if (res.success) {
                        $('#savedIssueNumber').text(res.issue_number || '-');
                        $('#step3DetailBody').html(res.html || '');
                        if (res.redirect) {
                            $('#viewIssueBtn').attr('href', res.redirect);
                        }
                        goToStep(3);
                        return;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถบันทึกข้อมูลได้'
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

        $('#reviewBackBtn').on('click', function() {
            goToStep(1);
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

            // ถามยืนยันก่อนส่งเข้าระบบจริง
            Swal.fire({
                icon: 'question',
                title: 'ยืนยันการบันทึกเข้าระบบ?',
                text: 'เมื่อบันทึกแล้วจะไม่สามารถแก้ไขข้อมูลนี้ในหน้านี้ได้อีก',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'กำลังบันทึก...',
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

        $('#issueForm').on('input change', 'input, select, textarea', updateReviewButtonState);
        $('#noUrlCheckbox').on('change', function() {
            if ($(this).is(':checked')) {
                $('#urlInput').val('').prop('disabled', true);
            } else {
                $('#urlInput').prop('disabled', false);
            }
            updateReviewButtonState();
        });

        if ($('#noUrlCheckbox').is(':checked')) {
            $('#urlInput').val('').prop('disabled', true);
        }

        updateReviewButtonState();
        setActiveStep(1);
    </script>
@endsection