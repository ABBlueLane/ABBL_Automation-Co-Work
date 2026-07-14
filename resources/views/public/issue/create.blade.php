@extends('layouts.public')
@section('navbar_container', 'container')
@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap');

        :root {
            --body-bg: #f8fafc;
            --card-bg: #ffffff;
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-soft: #eff6ff;
            --border-color: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body, input, select, textarea, button {
            font-family: 'Inter', 'Sarabun', sans-serif !important;
        }

        body {
            background-color: var(--body-bg) !important;
        }

        .content-wrapper {
            padding-bottom: 120px; /* Space for the fixed footer */
        }

        /* Header Banner redesign */
        .header-banner-section {
            border-bottom: 1px solid #e2e8f0;
            padding: 24px 0;
            margin-bottom: 30px;
            background-color: #ffffff;
        }
        .header-left-bar {
            border-left: 3px solid #cbd5e1;
            padding-left: 16px;
        }
        .h-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            line-height: 1.2;
        }
        .h-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 4px 0 0 0;
        }
        .breadcrumb-item a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
        }
        .breadcrumb-item a:hover {
            color: var(--primary-blue);
        }
        .breadcrumb-item.active {
            color: var(--text-main);
            font-weight: 700;
        }

        /* Stepper progress */
        .stepper-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            max-width: 500px;
            margin: 2rem auto 3rem auto;
        }
        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 3;
            position: relative;
            width: 100px;
        }
        .stepper-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #f1f5f9;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            border: 4px solid var(--body-bg);
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: all 0.25s ease;
        }
        .stepper-item.active .stepper-circle {
            background-color: var(--primary-blue);
            color: #ffffff;
            box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.15);
        }
        .stepper-item.done .stepper-circle {
            background-color: var(--primary-blue);
            color: #ffffff;
        }
        .stepper-label {
            margin-top: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            transition: color 0.25s ease;
            white-space: nowrap;
        }
        .stepper-item.active .stepper-label,
        .stepper-item.done .stepper-label {
            color: var(--text-main);
        }
        .stepper-line-1, .stepper-line-2 {
            position: absolute;
            top: 22px; /* middle of circle */
            height: 3px;
            background-color: #e2e8f0;
            z-index: 1;
        }
        .stepper-line-1 {
            left: 50px;
            width: calc(50% - 50px);
        }
        .stepper-line-2 {
            right: 50px;
            width: calc(50% - 50px);
        }
        .stepper-line-1.active, .stepper-line-2.active {
            background-color: var(--primary-blue);
        }

        /* Card styles */
        .main-form-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
            padding: 40px;
            margin-bottom: 40px;
        }
        .card-header-clean {
            border-bottom: none;
            background: transparent;
            padding: 0;
        }
        .card-title-clean {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            margin: 0;
        }
        .card-title-clean i {
            font-size: 1.4rem;
            margin-right: 8px;
        }

        /* Fields formatting */
        .field-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }
        .input-clean {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            color: var(--text-main);
            background-color: #ffffff;
            transition: all 0.2s ease;
        }
        .input-clean::placeholder {
            color: #94a3b8;
        }
        .input-clean:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
            outline: none;
        }
        .select-clean {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2364748b'%3e%3cpath d='M12 16L6 10H18L12 16Z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 20px;
            padding-right: 48px;
        }
        .textarea-clean {
            resize: vertical;
            min-height: 140px;
        }

        /* Priority segmented selector */
        .priority-segment {
            display: inline-flex;
            background-color: #f1f5f9;
            border-radius: 30px;
            padding: 4px;
            border: 1.5px solid #e2e8f0;
            width: 100%;
            max-width: 320px;
            justify-content: space-between;
        }
        .priority-segment input[type="radio"] {
            display: none;
        }
        .priority-label-btn {
            flex: 1;
            text-align: center;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0;
            user-select: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .priority-label-btn::before {
            content: "●";
            margin-right: 6px;
            display: none;
            font-size: 0.65rem;
        }
        /* High priority checked */
        .priority-segment input[value="high"]:checked + .priority-label-btn {
            background-color: #c5221f; /* dark red */
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(197, 34, 31, 0.2);
        }
        .priority-segment input[value="high"]:checked + .priority-label-btn::before {
            display: inline-block;
        }
        /* Medium priority checked */
        .priority-segment input[value="medium"]:checked + .priority-label-btn {
            background-color: #f59e0b; /* amber */
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2);
        }
        .priority-segment input[value="medium"]:checked + .priority-label-btn::before {
            display: inline-block;
        }
        /* Low priority checked */
        .priority-segment input[value="low"]:checked + .priority-label-btn {
            background-color: #10b981; /* emerald green */
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
        }
        .priority-segment input[value="low"]:checked + .priority-label-btn::before {
            display: inline-block;
        }

        /* Upload & Link sections */
        .section-separator {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 32px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .section-separator i {
            margin-right: 8px;
            font-size: 1.25rem;
        }

        /* Dropzone premium styling */
        .dropzone {
            border: 2px dashed #cbd5e1 !important;
            background: #f8fafc !important;
            border-radius: 12px !important;
            min-height: 160px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            flex-wrap: wrap;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 20px !important;
        }
        .dropzone.dz-started {
            flex-direction: row;
            align-items: flex-start;
            justify-content: flex-start;
            align-content: flex-start;
        }
        .dropzone:hover {
            border-color: var(--primary-blue) !important;
            background-color: var(--primary-blue-soft) !important;
        }
        .active-dropzone {
            border-color: var(--primary-blue) !important;
            background-color: var(--primary-blue-soft) !important;
        }
        .dz-message {
            margin: 0 !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .dropzone-link {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.15s ease;
        }
        .dropzone-subtext {
            color: var(--text-muted);
            font-size: 0.75rem;
            margin-top: 4px;
        }

        /* Uploaded file previews — horizontal grid */
        .dropzone .dz-preview {
            display: flex !important;
            float: none !important;
            flex-direction: column;
            align-items: center;
            width: 120px !important;
            min-height: auto !important;
            margin: 0 !important;
            position: relative;
        }
        .dropzone .dz-preview .dz-image {
            width: 120px !important;
            height: 120px !important;
            border-radius: 12px !important;
            overflow: hidden;
            background: #e2e8f0;
        }
        .dropzone .dz-preview .dz-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .dropzone .dz-preview .dz-details {
            display: none !important;
        }
        .dropzone .dz-preview .dz-progress,
        .dropzone .dz-preview .dz-success-mark,
        .dropzone .dz-preview .dz-error-mark {
            display: none !important;
        }
        .dropzone .dz-preview .dz-remove {
            display: inline-block;
            margin-top: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #ef4444 !important;
            text-decoration: none !important;
            text-align: center;
        }
        .dropzone .dz-preview .dz-remove:hover {
            color: #dc2626 !important;
            text-decoration: underline !important;
        }
        .dropzone .dz-preview.dz-add-more {
            width: 120px !important;
            margin: 0 !important;
        }
        .dropzone .dz-preview.dz-add-more .dz-add-tile {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 120px;
            height: 120px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            cursor: pointer;
            font-size: 2rem;
            font-weight: 300;
            color: #94a3b8;
            background: #fff;
            transition: border-color 0.2s ease, color 0.2s ease, background-color 0.2s ease;
        }
        .dropzone .dz-preview.dz-add-more .dz-add-tile:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            background: var(--primary-blue-soft);
        }

        /* Input with icon link */
        .input-group-icon-wrap {
            position: relative;
            width: 100%;
        }
        .input-group-icon-wrap .icon-prefix {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            font-size: 1.15rem;
            z-index: 4;
        }
        .input-group-icon-wrap .input-with-icon {
            padding-left: 44px !important;
        }

        /* Sticky Footer Bar */
        .form-footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 16px 40px;
            display: flex;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.03);
            height: 72px;
        }
        .btn-cancel {
            background-color: #e2e8f0;
            color: #475569;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-cancel:hover {
            background-color: #cbd5e1;
            color: #334155;
        }
        .text-save-draft {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.85rem;
        }
        .btn-continue {
            background-color: var(--primary-blue);
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
        }
        .btn-continue:hover {
            background-color: var(--primary-blue-hover);
            color: #ffffff;
        }
        .btn-continue:disabled {
            background-color: #93c5fd;
            cursor: not-allowed;
        }
        .btn-success-custom {
            background-color: #10b981;
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
        }
        .btn-success-custom:hover {
            background-color: #059669;
            color: #ffffff;
        }

        /* Review summaries & step 2 design */
        .review-summary {
            padding: 10px 0;
        }
        .review-field-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 22px;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.01);
        }
        .review-field-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
        }
        .review-field-label i {
            font-size: 1rem;
            margin-right: 6px;
            color: var(--primary-blue);
        }
        .review-field-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-main);
            word-break: break-word;
        }
        .review-priority-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .review-files-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 8px;
        }
        .review-file-item {
            width: 120px;
            text-align: center;
        }
        .review-file-thumb {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .review-file-thumb > a {
            display: block;
            width: 100%;
            height: 100%;
            text-decoration: none !important;
            color: transparent;
            overflow: hidden;
        }
        .review-file-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            cursor: zoom-in;
        }
        .review-file-thumb .review-file-icon {
            font-size: 2rem;
            color: #94a3b8;
        }
        .review-file-name {
            margin-top: 8px;
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--text-muted);
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }

        /* Step 3 Success UI */
        .success-card {
            text-align: center;
            padding: 40px 20px;
        }
        .success-icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background-color: #ecfdf5;
            color: #10b981;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 24px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.1);
        }
        .ims-badge {
            display: inline-block;
            background-color: var(--primary-blue-soft);
            color: #1e40af;
            font-size: 1.15rem;
            font-weight: 700;
            padding: 10px 24px;
            border-radius: 10px;
            letter-spacing: 0.02em;
            margin-top: 10px;
            border: 1px solid #bfdbfe;
        }
    </style>

    <!-- Header Banner -->
    <div class="header-banner-section">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="header-left-bar">
                <h1 class="h-title">ระบบจัดการปัญหา</h1>
                <p class="h-subtitle">สร้างรายงานปัญหาใหม่สำหรับธุรกิจของคุณ</p>
            </div>
            <div class="header-right-breadcrumbs">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('business.select') }}">ธุรกิจ</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('issue.index', $business) }}">จัดการปัญหา</a></li>
                        <li class="breadcrumb-item active" aria-current="page">แจ้งปัญหา</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="container">
            <!-- Stepper Container -->
            <div class="stepper-container" id="issueStepper">
                <div class="stepper-line-1" id="stepperLine1"></div>
                <div class="stepper-line-2" id="stepperLine2"></div>
                
                <div class="stepper-item" data-step="1">
                    <div class="stepper-circle">1</div>
                    <div class="stepper-label">กรอกข้อมูล</div>
                </div>
                <div class="stepper-item" data-step="2">
                    <div class="stepper-circle">2</div>
                    <div class="stepper-label">สรุปข้อมูล</div>
                </div>
                <div class="stepper-item" data-step="3">
                    <div class="stepper-circle">3</div>
                    <div class="stepper-label">บันทึกสำเร็จ</div>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="main-form-card">
                {{-- Step 1: ฟอร์มกรอกข้อมูล --}}
                <div id="stepPanel1" class="wizard-step">
                    <div class="card-header-clean mb-4">
                        <h2 class="card-title-clean">
                            <i class="ri-information-line"></i>ข้อมูลปัญหา
                        </h2>
                    </div>

                    <form id="issueForm" action="#" method="POST">
                        @csrf
                        <input type="hidden" name="draft_issue_id" id="draftIssueId"
                            value="{{ ($issue?->status ?? null) === \App\Models\Issue::STATUS_DRAFT ? $issue->id : '' }}">

                        <div class="row g-4">
                            {{-- หัวข้อปัญหา --}}
                            <div class="col-md-8">
                                <label class="field-label">หัวข้อปัญหา <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="input-clean"
                                    value="{{ old('title', $issue?->title ?? '') }}" placeholder="ปัญหา">
                            </div>

                            {{-- ระดับ --}}
                            <div class="col-md-4">
                                <label class="field-label">ระดับ <span class="text-danger">*</span></label>
                                <div class="priority-segment-container">
                                    <div class="priority-segment">
                                        @php
                                            $selectedPriority = (string) old('priority', $issue?->priority ?? \App\Models\Issue::PRIORITY_HIGH);
                                            $priorityOrder = [
                                                \App\Models\Issue::PRIORITY_HIGH => 'เร่งด่วน',
                                                \App\Models\Issue::PRIORITY_MEDIUM => 'กลาง',
                                                \App\Models\Issue::PRIORITY_LOW => 'ต่ำ',
                                            ];
                                        @endphp
                                        @foreach ($priorityOrder as $value => $label)
                                            <input type="radio" name="priority" id="priority_{{ $value }}"
                                                value="{{ $value }}" @checked($selectedPriority === (string) $value)>
                                            <label for="priority_{{ $value }}" class="priority-label-btn {{ $value }}">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- โปรเจค --}}
                            <div class="col-md-4">
                                <label class="field-label">โปรเจค <span class="text-danger">*</span></label>
                                @php
                                    $currentBusiness = \App\Models\Business::find($business);
                                    $selectedIssueProjectId = old(
                                        'issue_project_id',
                                        $issue?->issue_project_id
                                            ?? optional($issueProjects->firstWhere('name', $currentBusiness?->business_name))->id
                                            ?? ''
                                    );
                                @endphp
                                <select name="issue_project_id" id="issue_project_id" class="input-clean select-clean">
                                    <option value="">เลือกโปรเจค</option>
                                    @foreach ($issueProjects as $project)
                                        <option value="{{ $project->id }}"
                                            @selected((string) $selectedIssueProjectId === (string) $project->id)>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- รายละเอียด --}}
                            <div class="col-12">
                                <label class="field-label">รายละเอียด <span class="text-danger">*</span></label>
                                <textarea name="comment" id="commentTextarea" rows="6" class="input-clean textarea-clean"
                                    placeholder="อธิบายรายละเอียด">{{ old('comment', $issue?->firstComment->comment ?? '') }}</textarea>
                            </div>

                            {{-- แนบไฟล์ + แนบลิงค์ --}}
                            <div class="col-12">
                                <div class="section-separator">
                                    <i class="ri-attachment-2"></i>อัปโหลดไฟล์ หรือแนบลิงค์
                                </div>
                            </div>

                            {{-- อัปโหลด --}}
                            <div class="col-md-6">
                                <label class="field-label">อัปโหลด</label>
                                <div class="dropzone" id="mediaDropzone" tabindex="0">
                                    <div class="dz-message py-3">
                                        <i class="ri-cloud-line" style="font-size: 2.2rem; color: #2563eb; margin-bottom: 8px;"></i>
                                        <p class="mb-1" style="font-size: 0.9rem;">
                                            <span id="browseTrigger" class="dropzone-link">ลากไฟล์มาวาง & คลิกที่นี่เพื่ออัปโหลด</span>
                                        </p>
                                        <span class="dropzone-subtext">สูงสุด: 50MB</span>
                                    </div>
                                </div>
                            </div>

                            {{-- แนบลิงค์ --}}
                            <div class="col-md-6">
                                <label class="field-label">แนบลิงค์</label>
                                <div class="input-group-icon-wrap">
                                    <i class="ri-link-m icon-prefix"></i>
                                    <input type="url" name="url" id="urlInput" class="input-clean input-with-icon"
                                        value="{{ old('url', $issue?->url ?? '') }}" placeholder="https://example.com">
                                </div>
                                <input type="checkbox" id="noUrlCheckbox" style="display: none;" @checked(empty(old('url', $issue?->url ?? '')))>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Step 2: สรุปข้อมูล + ปุ่มบันทึก --}}
                <div id="stepPanel2" class="wizard-step d-none">
                    <div class="card-header-clean mb-4">
                        <h2 class="card-title-clean">
                            <i class="ri-eye-line text-primary"></i>ตรวจสอบข้อมูลก่อนบันทึก
                        </h2>
                        <p class="text-muted small mb-0 mt-1">กรุณาตรวจสอบความถูกต้องก่อนกดบันทึกเข้าระบบ</p>
                    </div>
                    <div id="issueReviewBody"></div>
                </div>

                {{-- Step 3: ผลการบันทึก + เลข IMS --}}
                <div id="stepPanel3" class="wizard-step d-none">
                    <div class="success-card">
                        <div class="success-icon-wrap">
                            <i class="ri-checkbox-circle-line"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-2">บันทึกข้อมูลสำเร็จ</h3>
                        <p class="text-muted mb-3">รายการของคุณถูกส่งเข้าระบบเรียบร้อยแล้ว</p>
                        <div class="mb-1 text-muted small fw-medium">เลข IMS</div>
                        <div class="ims-badge" id="savedIssueNumber">-</div>
                    </div>
                    <div id="step3DetailBody" class="mt-4"></div>
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                        <a href="{{ route('issue.index', $business) }}" class="btn btn-cancel">
                            <i class="ri-list-check me-2"></i> กลับหน้ารายการ
                        </a>
                        <a href="#" class="btn btn-continue" id="viewIssueBtn">
                            <i class="ri-external-link-line me-2"></i> ดูรายละเอียด
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Redesigned sticky footer actions -->
    <div class="form-footer-bar">
        <div class="container d-flex align-items-center justify-content-between">
            <!-- Step 1 Actions -->
            <div id="step1Actions" class="d-flex w-100 justify-content-between align-items-center">
                <a href="{{ route('issue.index', $business) }}" class="btn btn-cancel">ยกเลิก</a>
                <span class="text-save-draft text-muted">กด Ctrl+S เพื่อบันทึกแบบร่าง</span>
                <button type="button" class="btn btn-continue" id="reviewBtn">
                    ถัดไป <i class="ri-arrow-right-line ms-2"></i>
                </button>
            </div>
            <!-- Step 2 Actions -->
            <div id="step2Actions" class="d-flex w-100 justify-content-between align-items-center d-none">
                <button type="button" class="btn btn-cancel" id="reviewBackBtn">
                    <i class="ri-arrow-left-line me-2"></i> กลับไปแก้ไข
                </button>
                <span></span>
                <button type="button" class="btn btn-success-custom" id="reviewSubmitBtn">
                    บันทึกเข้าระบบ <i class="ri-save-line ms-2"></i>
                </button>
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
        const storageBaseUrl = @json(asset('storage'));
        let draftIssueId = $('#draftIssueId').val() || '';
        let pendingQueueAction = null;
        let currentStep = 1;

        function setActiveStep(step) {
            currentStep = step;
            document.querySelectorAll('#issueStepper .stepper-item').forEach(function(item) {
                const stepNum = parseInt(item.dataset.step, 10);
                const circle = item.querySelector('.stepper-circle');
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

            // Update stepper connecting lines
            const line1 = document.getElementById('stepperLine1');
            const line2 = document.getElementById('stepperLine2');
            if (line1) {
                line1.classList.toggle('active', step >= 1);
            }
            if (line2) {
                line2.classList.toggle('active', step >= 2);
            }
        }

        function goToStep(step) {
            document.querySelectorAll('.wizard-step').forEach(function(panel) {
                panel.classList.add('d-none');
            });
            document.getElementById('stepPanel' + step).classList.remove('d-none');

            // Toggle form footer buttons
            if (step === 1) {
                $('.form-footer-bar').removeClass('d-none');
                $('#step1Actions').removeClass('d-none');
                $('#step2Actions').addClass('d-none');
            } else if (step === 2) {
                $('.form-footer-bar').removeClass('d-none');
                $('#step1Actions').addClass('d-none');
                $('#step2Actions').removeClass('d-none');
            } else if (step === 3) {
                $('.form-footer-bar').addClass('d-none');
            }

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
            dictRemoveFile: "ลบไฟล์",
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
                myDropzone.emit("thumbnail", mockFile, storageBaseUrl + "/" + filePath);
            } else if (['mp4', 'webm', 'mov'].includes(ext)) {
                let video = document.createElement('video');
                video.src = storageBaseUrl + "/" + filePath;
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
            preview.classList.add("dz-add-more");

            preview.innerHTML = `<div class="dz-add-tile" title="เพิ่มไฟล์">+</div>`;

            preview.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                myDropzone.hiddenFileInput.click();
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
            file.serverPath = response.path;
            if (response.name) {
                file.originalName = response.name;
            }
        });

        myDropzone.on("removedfile", function(file) {
            if (file.isAddButton) return;

            const pathName = (file.serverPath || '').split('/').pop();

            existingFiles = existingFiles.filter(path => {
                const base = path.split('/').pop();
                return base !== file.name && base !== pathName;
            });

            uploadedFiles = uploadedFiles.filter(path => {
                const base = path.split('/').pop();
                return base !== file.name && base !== pathName && path !== file.serverPath;
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
        document.getElementById('commentTextarea').addEventListener('paste', handlePasteEvent);

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
                    return 'กรุณากรอกลิงก์ หรือปล่อยว่างหากไม่มีลิงก์';
                }
            }
            return null;
        }

        function updateReviewButtonState() {
            const isComplete = validateBeforeReview() === null && !duplicateCommentBlocked();
            $('#reviewBtn').prop('disabled', !isComplete);
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
                high: { color: '#c5221f', label: 'เร่งด่วน' },
                medium: { color: '#f59e0b', label: 'กลาง' },
                low: { color: '#10b981', label: 'ต่ำ' },
            };
            return map[value] || { color: '#6c757d', label: '-' };
        }

        function isImageFileName(name) {
            return /\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(name || '');
        }

        function getReviewFiles() {
            const pathByBase = {};
            [...existingFiles, ...uploadedFiles].forEach(function(path) {
                if (!path || path === 'add-more') return;
                pathByBase[String(path).split('/').pop()] = path;
            });

            const fromDropzone = myDropzone.files
                .filter(function(file) { return !file.isAddButton; })
                .map(function(file) {
                    const name = file.originalName || file.name || 'file';
                    const path = file.serverPath || pathByBase[file.name] || null;
                    const imgEl = file.previewElement && file.previewElement.querySelector('.dz-image img');
                    const isImage = isImageFileName(name)
                        || isImageFileName(path)
                        || !!(file.type && file.type.indexOf('image/') === 0)
                        || !!(imgEl && imgEl.src);

                    let url = null;
                    if (imgEl && imgEl.src) {
                        url = imgEl.src;
                    } else if (path && isImage) {
                        url = storageBaseUrl + '/' + path;
                    }

                    return {
                        name: name,
                        path: path,
                        url: url,
                        isImage: isImage,
                    };
                });

            if (fromDropzone.length > 0) {
                return fromDropzone;
            }

            return [...existingFiles, ...uploadedFiles]
                .filter(function(path) { return path && path !== 'add-more'; })
                .map(function(path) {
                    const name = String(path).split('/').pop();
                    const isImage = isImageFileName(name);
                    return {
                        name: name,
                        path: path,
                        url: isImage ? (storageBaseUrl + '/' + path) : null,
                        isImage: isImage,
                    };
                });
        }

        function renderFormReviewSummary() {
            const title = $('input[name="title"]').val().trim();
            const projectName = $('#issue_project_id option:selected').text().trim();
            const comment = $('#commentTextarea').val().trim();
            const noUrl = $('#noUrlCheckbox').is(':checked');
            const url = noUrl ? '' : ($('#urlInput').val() || '').trim();
            const priority = getPriorityReviewMeta();
            const files = getReviewFiles();

            let filesHtml = '<div class="review-field-value text-muted">-</div>';
            if (files.length > 0) {
                filesHtml = '<div class="review-files-grid">' + files.map(function(file) {
                    let thumbInner;
                    if (file.isImage && file.url) {
                        thumbInner = '<a href="' + escapeHtml(file.url) + '" target="_blank" rel="noopener">' +
                            '<img src="' + escapeHtml(file.url) + '" alt="" loading="lazy" ' +
                            'onerror="this.onerror=null;this.parentElement.outerHTML=\'<i class=&quot;ri-image-line review-file-icon&quot;></i>\'">' +
                            '</a>';
                    } else {
                        thumbInner = '<i class="ri-file-line review-file-icon"></i>';
                    }
                    return '<div class="review-file-item">' +
                        '<div class="review-file-thumb">' + thumbInner + '</div>' +
                        '<div class="review-file-name" title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</div>' +
                        '</div>';
                }).join('') + '</div>';
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
                                <div class="review-field-label"><i class="ri-edit-line"></i> หัวข้อปัญหา</div>
                                <div class="review-field-value">${escapeHtml(title)}</div>
                                <div class="review-field-label mt-3"><i class="ri-building-2-line"></i> โปรเจค</div>
                                <div class="review-field-value">${escapeHtml(projectName || '-')}</div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="review-field-box text-center d-flex flex-column align-items-center justify-content-center">
                                <div class="review-field-label mb-2">ระดับความเร่งด่วน</div>
                                <div class="d-inline-flex align-items-center bg-light px-3 py-2 rounded-pill border">
                                    <span class="review-priority-dot" style="background-color: ${priority.color};"></span>
                                    <span class="fw-bold" style="color: ${priority.color};">${escapeHtml(priority.label)}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="review-field-box">
                                <div class="review-field-label"><i class="ri-file-text-line"></i> รายละเอียด</div>
                                <div class="review-field-value" style="white-space: pre-wrap;">${escapeHtml(comment)}</div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="review-field-box">
                                <div class="review-field-label"><i class="ri-attachment-2"></i> แนบไฟล์</div>
                                ${filesHtml}
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="review-field-box">
                                <div class="review-field-label"><i class="ri-links-line"></i> แนบลิงค์</div>
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

        // Sync hidden checkbox state based on URL input
        function syncNoUrlCheckbox() {
            const val = $('#urlInput').val().trim();
            $('#noUrlCheckbox').prop('checked', val === '');
        }

        $('#urlInput').on('input change', function() {
            syncNoUrlCheckbox();
            updateReviewButtonState();
        });

        // Initialize state on load
        syncNoUrlCheckbox();
        updateReviewButtonState();
        setActiveStep(1);
    </script>
@endsection