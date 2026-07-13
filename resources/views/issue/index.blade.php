@extends('api_clients.layout')

@section('title', 'Issue Management')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/issue-index.css') }}">
@endpush

@section('content')
    <div class="issue-page">
        {{-- <nav class="issue-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('dashboard') }}">Business</a>
            <span class="issue-breadcrumb-sep">&gt;</span>
            <span>Issue Management</span>
        </nav> --}}

        <div class="issue-page-header">
            <div>
                <h1 class="issue-page-title">Issue Management</h1>
                <p class="issue-page-subtitle">จัดการและติดตาม Issue ทั้งหมดในระบบอย่างมีประสิทธิภาพ</p>
            </div>
            <a href="{{ route('issue.create') }}" class="btn btn-primary issue-create-btn">
                <i class="ri-add-line"></i> สร้าง Issue
            </a>
        </div>

        <div class="issue-toolbar">
            <div class="issue-search-row">
                <div class="issue-search-wrap">
                    <i class="ri-search-line issue-search-icon"></i>
                    <input type="text" class="issue-search-input" id="qt_wording" placeholder="ค้นหาชื่อโปรเจค หรือ Issue ID...">
                </div>
                <button type="button" class="issue-reset-link" onclick="resetFilter();">รีเซ็ตตัวกรอง</button>
            </div>

            <div class="issue-filter-row">
                <span class="issue-filter-by-label">กรองตาม:</span>
                <div class="issue-filter-pills">
                    <input type="hidden" id="qt_priority" value="">
                    <button type="button" class="issue-pill btn-priority-filter active" data-priority="">ทั้งหมด</button>
                    <button type="button" class="issue-pill btn-priority-filter" data-priority="high">ด่วน</button>
                    <button type="button" class="issue-pill btn-priority-filter" data-priority="medium">กลาง</button>
                    <button type="button" class="issue-pill btn-priority-filter" data-priority="low">น้อย</button>
                </div>
                <button type="button" class="issue-advanced-toggle" id="toggleAdvancedFilters" aria-expanded="false">
                    <i class="ri-equalizer-line"></i> ตัวกรองเพิ่มเติม
                    <i class="ri-arrow-down-s-line issue-advanced-chevron"></i>
                </button>
            </div>

            <div class="issue-advanced-filters" id="advancedFilters" hidden>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label class="issue-advanced-label" for="qt_business_id">ธุรกิจ</label>
                        <select id="qt_business_id" class="select issue-advanced-select">
                            <option value="">ทั้งหมด</option>
                            @foreach ($businesses as $business)
                                <option value="{{ $business->id }}">{{ $business->business_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="issue-advanced-label" for="qt_status">สถานะ</label>
                        <select id="qt_status" class="select issue-advanced-select">
                            <option value="">ทั้งหมด</option>
                            <option value="open">ยังไม่ปิดงาน (Open)</option>
                            <option value="pending">รอรีวิว</option>
                            <option value="in_progress">กำลังดำเนินการ</option>
                            <option value="waiting_review">รอตรวจ</option>
                            <option value="customer_replied">ลูกค้าตอบกลับแล้วรอทีมงานดำเนินการ</option>
                            <option value="done">ดำเนินการแล้ว</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="issue-advanced-label" for="qt_issue_project">โปรเจค</label>
                        <select id="qt_issue_project" class="select issue-advanced-select">
                            <option value="">ทั้งหมด</option>
                            @foreach ($issueProjects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="issue-advanced-label" for="qt_assigned_to">ผู้รับผิดชอบ</label>
                        <select id="qt_assigned_to" class="select issue-advanced-select">
                            <option value="">ทั้งหมด</option>
                            <option value="null">ยังไม่มอบหมาย</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="issue-cards-area">
            <div class="text-center py-5" id="showLoading">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="text-muted">กำลังโหลดข้อมูล...</div>
            </div>

            <div id="showCards" style="display:none;">
                <div class="row g-4" id="issueCardContainer"></div>
                <div class="issue-pagination-bar">
                    <div class="issue-pagination-info" id="issuePaginationInfo"></div>
                    <nav>
                        <ul class="pagination issue-pagination mb-0" id="issuePaginationContainer"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade issue-comment-modal" id="issueCommentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">ความคิดเห็นทั้งหมด</h5>
                        <p class="mb-0 text-muted small">ดูประวัติและเพิ่มความคิดเห็นใหม่ได้จากที่นี่</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="commentList" class="issue-comment-list"></div>
                    <form id="commentForm" class="mt-3">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" id="commentBusinessId" name="business_id" value="">
                        <label class="form-label fw-bold small text-muted">เพิ่มความคิดเห็น</label>
                        <textarea id="commentInput" name="comment" class="form-control" rows="4" placeholder="พิมพ์ข้อความสำหรับคอมเม้นต์..."></textarea>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary btn-sm">ส่งความคิดเห็น</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('issue.partials.card-renderer', [
        'tableUrl' => route('admin.issues.table'),
        'commentsUrlTemplate' => route('issue.comments.index', ['issue' => '__ISSUE_ID__']),
        'commentStoreUrlTemplate' => route('issue.comment.store', ['issue' => '__ISSUE_ID__']),
        'showBusinessBadge' => true,
    ])
@endsection
