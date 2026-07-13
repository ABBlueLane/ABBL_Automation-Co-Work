@extends('layouts.public')
@section('title', 'OneClick | ผู้ใช้งานระบบ')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/issue-index.css') }}">
@endsection

@section('content')
    <div class="container-fluid py-4">
        {{-- Header Section --}}
        <div class="d-flex align-items-center mb-4 flex-wrap gap-3">
            <h4 class="mb-0 flex-grow-1 fw-bold text-dark" style="font-size: 1.4rem;">
                รายการ Issue {{ $business->business_name }}
            </h4>
            <div class="flex-shrink-0 ms-auto">
                <a href="{{ route('issue.create', $business) }}" class="btn btn-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3 py-2 shadow-sm">
                    <i class="ri-add-line align-bottom fs-5"></i>
                    เพิ่ม Issue
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                {{-- Filter Section --}}
                <div class="border rounded p-3 mb-4 bg-white shadow-sm">
                    <div class="row g-3 align-items-end">
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label fw-bold text-muted small">
                                <i class="ri-search-line me-1"></i> คำค้นหา
                            </label>
                            <input type="text" class="form-control" id="qt_wording" placeholder="พิมพ์คำค้นหา...">
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6">
                            <label class="form-label fw-bold text-muted small">
                                <i class="ri-filter-3-line me-1"></i> สถานะ
                            </label>
                            <select id="qt_status" class="select">
                                <option value="">ทั้งหมด</option>
                                <option value="draft">แบบร่าง</option>
                                <option value="pending">รอรีวิว</option>
                                <option value="in_progress">กำลังดำเนินการ</option>
                                <option value="waiting_review">รอตรวจ</option>
                                <option value="customer_replied">ลูกค้าตอบกลับแล้วรอทีมงานดำเนินการ</option>
                                <option value="done">ดำเนินการแล้ว</option>
                            </select>
                        </div>
                        
                        {{-- ย้ายฟิลเตอร์ระดับความเร่งด่วนมาไว้ข้างๆ สถานะ --}}
                        <div class="col-xl-4 col-lg-5 col-md-12">
                            <label class="form-label fw-bold text-muted small d-block">ระดับความเร่งด่วน</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="hidden" id="qt_priority" value="">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-priority-filter flex-fill" data-priority="high" style="border-radius: 6px; padding: .42rem .6rem;">
                                    <i class="ri-alert-fill"></i> ด่วน
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm btn-priority-filter flex-fill" data-priority="medium" style="border-radius: 6px; padding: .42rem .6rem;">
                                    <i class="ri-alert-line"></i> กลาง
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm btn-priority-filter flex-fill" data-priority="low" style="border-radius: 6px; padding: .42rem .6rem;">
                                    <i class="ri-checkbox-blank-circle-line"></i> น้อย
                                </button>
                            </div>
                        </div>

                        <div class="col-xl-2 col-lg-12 col-md-12 d-flex gap-2 justify-content-lg-start justify-content-xl-end">
                            <button class="btn btn-primary btn-sm rounded d-flex align-items-center gap-1 px-3" type="button" onclick="reloadCards();" style="height: 38px;">
                                <i class="ri-search-line"></i> ค้นหา
                            </button>
                            <button class="btn btn-outline-secondary btn-sm rounded d-flex align-items-center gap-1 px-3" type="button" onclick="resetFilter();" style="height: 38px;">
                                <i class="ri-refresh-line"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
                
                {{-- Loading --}}
                <div class="text-center py-5" id="showLoading">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="text-muted">กำลังโหลดข้อมูล...</div>
                </div>
                
                {{-- Card Container --}}
                <div id="showCards" style="display:none;">
                    <div class="row g-3" id="issueCardContainer">
                        {{-- รายการ Card จะถูกสร้างขึ้นจาก JavaScript ตรงนี้ --}}
                    </div>
                    
                    {{-- แถบปุ่มเปลี่ยนหน้า Pagination --}}
                    <div class="d-flex justify-content-center mt-5 pt-2">
                        <nav>
                            <ul class="pagination pagination-sm mb-0 gap-1" id="issuePaginationContainer" style="--bs-pagination-border-radius: 4px;">
                                {{-- ปุ่มเปลี่ยนหน้า Pagination --}}
                            </ul>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        let currentPage = 1;
        const itemsPerPage = 6;
        let activeIssueId = null;
        const commentsUrlTemplate = "{{ route('issue.comments.index', [$business, '__ISSUE_ID__']) }}";
        const commentStoreUrlTemplate = "{{ route('issue.comment.store', [$business, '__ISSUE_ID__']) }}";

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function(match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[match];
            });
        }

        $(document).ready(function() {
            $(".select").select2({
                width: "100%",
                minimumResultsForSearch: -1
            });
            
            fetchCardData();

            $("#qt_wording").keypress(function(e) {
                if (e.which == 13) {
                    reloadCards();
                }
            });
            $("#qt_status").on('change select2:select select2:unselect', reloadCards);

            $('.btn-priority-filter').on('click', function() {
                let clickedPriority = $(this).data('priority');
                let currentPriority = $('#qt_priority').val();

                if (currentPriority === clickedPriority) {
                    $('#qt_priority').val('');
                    $('.btn-priority-filter').removeClass('active');
                } else {
                    $('#qt_priority').val(clickedPriority);
                    $('.btn-priority-filter').removeClass('active');
                    $(this).addClass('active');
                }
                
                reloadCards();
            });

            // จัดการเมื่อกดปุ่ม "ดูรายละเอียด" ให้เปิดหน้า detail โดยตรง
            $(document).on('click', '.issue-view-btn', function(e) {
                e.preventDefault();
                const targetUrl = $(this).data('url') || '#';
                if (targetUrl && targetUrl !== '#') {
                    window.location.href = targetUrl;
                }
            });

            // จัดการเมื่อกดปุ่ม "แก้ไข" ให้เปิดหน้าแก้ไขหรือหน้า detail โดยตรง
            $(document).on('click', '.issue-edit-btn', function(e) {
                e.preventDefault();
                const targetUrl = $(this).data('url') || '#';
                if (targetUrl && targetUrl !== '#') {
                    window.location.href = targetUrl;
                }
            });

            $(document).on('click', '.issue-comment-btn', function() {
                activeIssueId = $(this).data('issue-id');
                $('#commentList').html('<div class="issue-comment-empty">กำลังโหลดความคิดเห็น...</div>');
                $('#commentInput').val('');
                $('#issueCommentModal').modal('show');
                loadComments(activeIssueId);
            });

            $('#commentForm').on('submit', function(e) {
                e.preventDefault();

                if (!activeIssueId) {
                    return;
                }

                const commentText = $('#commentInput').val().trim();
                if (!commentText) {
                    Swal.fire({ icon: 'warning', title: 'กรุณาพิมพ์ข้อความก่อนส่ง' });
                    return;
                }

                const url = commentStoreUrlTemplate.replace('__ISSUE_ID__', activeIssueId);
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        comment: commentText,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        $('#commentInput').val('');
                        loadComments(activeIssueId);
                        fetchCardData();
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถส่งความคิดเห็นได้'
                        });
                    }
                });
            });
        });

        function loadComments(issueId) {
            if (!issueId) {
                return;
            }

            const url = commentsUrlTemplate.replace('__ISSUE_ID__', issueId);
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    const comments = response.comments || [];
                    if (!comments.length) {
                        $('#commentList').html('<div class="issue-comment-empty">ยังไม่มีความคิดเห็นใน Issue นี้</div>');
                        return;
                    }

                    let html = '';
                    comments.forEach(function(comment) {
                        html += `
                            <div class="issue-comment-item">
                                <div class="issue-comment-user">${escapeHtml(comment.user?.full_name || 'ไม่ระบุ')}</div>
                                <div class="issue-comment-text">${escapeHtml(comment.comment || '')}</div>
                                <div class="issue-comment-time">${escapeHtml(comment.created_at || '')}</div>
                            </div>
                        `;
                    });

                    $('#commentList').html(html);
                },
                error: function() {
                    $('#commentList').html('<div class="issue-comment-empty">ไม่สามารถโหลดความคิดเห็นได้</div>');
                }
            });
        }

        function fetchCardData() {
            let startRow = (currentPage - 1) * itemsPerPage;

            $.ajax({
                url: "{{ route('issue.table', $business) }}",
                type: "GET",
                data: {
                    wording: $("#qt_wording").val(),
                    status: $("#qt_status").val(),
                    priority: $("#qt_priority").val(),
                    draw: 1,
                    start: startRow,
                    length: itemsPerPage
                },
                success: function(response) {
                    $("#showLoading").hide();
                    let container = $("#issueCardContainer");
                    container.empty();

                    // ซ่อน Tooltip เดิมที่อาจจะค้างอยู่ก่อนเคลียร์เนื้อหา
                    $('.tooltip').remove();

                    let items = response.data || [];
                    let totalItems = response.recordsFiltered !== undefined ? response.recordsFiltered : items.length;
                    
                    if (items.length === 0) {
                        container.append(`
                            <div class="col-12 text-center py-5 text-muted bg-white rounded border shadow-sm">
                                <i class="ri-inbox-line ri-2x mb-2 d-block"></i>
                                ไม่พบข้อมูลรายการ Issue
                            </div>
                        `);
                        $("#issuePaginationContainer").empty();
                    } else {
                        items.forEach(function(item) {
                            let priorityText = item.priority || 'กลาง'; 
                            let priorityBg = '#fff3cd'; 
                            let priorityTextColor = '#664d03'; 
                            let priorityBorderColor = '#ffc107';

                            if (priorityText.includes('ด่วน') || priorityText.toLowerCase() === 'high') {
                                priorityBg = '#f8d7da'; 
                                priorityTextColor = '#842029'; 
                                priorityBorderColor = '#dc3545';
                                priorityText = 'ด่วน';
                            } else if (priorityText.includes('น้อย') || priorityText.toLowerCase() === 'low') {
                                priorityBg = '#d1e7dd'; 
                                priorityTextColor = '#0f5132'; 
                                priorityBorderColor = '#198754';
                                priorityText = 'น้อย';
                            } else {
                                priorityText = 'กลาง';
                            }

                            let currentStatus = (item.status || 'pending').toString().toLowerCase().trim();
                            let statusMeta = {
                                'draft': {
                                    label: 'แบบร่าง',
                                    bg: 'background-color: #6c757d; color: #fff;',
                                    step: 0,
                                    linePercent: 0,
                                    displayPercent: 0
                                },
                                'pending': {
                                    label: 'รอรีวิว',
                                    bg: 'background-color: #ffc107; color: #fff;',
                                    step: 0,
                                    linePercent: 0,
                                    displayPercent: 25
                                },
                                'in_progress': {
                                    label: 'กำลังดำเนินการ',
                                    bg: 'background-color: #0d6efd; color: #fff;',
                                    step: 1,
                                    linePercent: 33.33,
                                    displayPercent: 50
                                },
                                'waiting_review': {
                                    label: 'รอตรวจ',
                                    bg: 'background-color: #0dcaf0; color: #000;',
                                    step: 2,
                                    linePercent: 66.66,
                                    displayPercent: 75
                                },
                                'customer_replied': {
                                    label: 'ลูกค้าตอบกลับแล้วรอทีมงานดำเนินการ',
                                    bg: 'background-color: #6f42c1; color: #fff;',
                                    step: 1,
                                    linePercent: 33.33,
                                    displayPercent: 50
                                },
                                'done': {
                                    label: 'ดำเนินการแล้ว',
                                    bg: 'background-color: #198754; color: #fff;',
                                    step: 3,
                                    linePercent: 100,
                                    displayPercent: 100
                                }
                            };

                            let statusInfo = statusMeta[currentStatus] || statusMeta['pending'];
                            let statusLabel = statusInfo.label;
                            let statusBg = statusInfo.bg;
                            let stepIndex = statusInfo.step;
                            let linePercent = statusInfo.linePercent;

                            let progressColor = '#dc3545'; 
                            if (statusInfo.displayPercent === 50 || statusInfo.displayPercent === 75) {
                                progressColor = '#ffc107'; 
                            } else if (statusInfo.displayPercent === 100) {
                                progressColor = '#198754'; 
                            }

                            let rawAssignee = item.assigned_to || '';
                            let assigneeHtml = '';
                            if (rawAssignee === '' || rawAssignee === '-' || rawAssignee.toLowerCase() === 'null') {
                                assigneeHtml = `<span class="text-muted fw-normal">ไม่มี</span>`;
                            } else {
                                assigneeHtml = `<span class="text-dark fw-bold">${escapeHtml(rawAssignee)}</span>`;
                            }

                            let commentCount = item.comments_count !== undefined ? parseInt(item.comments_count) : 0;

                            let targetUrl = item.view_url || '#';
                            let editUrl = item.edit_url || targetUrl; 
                            
                            let latestComment = item.latest_comment ? escapeHtml(item.latest_comment) : '';
                            let latestCommentUser = item.latest_comment_user ? escapeHtml(item.latest_comment_user) : '-';
                            let latestCommentDate = item.latest_comment_created_at ? escapeHtml(item.latest_comment_created_at) : '';
                            let commentPreviewHtml = latestComment
                                ? `
                                    <div class="issue-comment-preview">
                                        <div class="issue-comment-preview-label"><i class="ri-chat-1-line"></i> ความคิดเห็นล่าสุด</div>
                                        <div class="issue-comment-preview-text">${latestComment}</div>
                                        <div class="issue-comment-preview-meta">โดย ${latestCommentUser} • ${latestCommentDate}</div>
                                    </div>
                                `
                                : `
                                    <div class="issue-comment-preview empty">
                                        <div class="issue-comment-preview-label"><i class="ri-chat-1-line"></i> ความคิดเห็นล่าสุด</div>
                                        <div class="issue-comment-preview-text">ยังไม่มีความคิดเห็น</div>
                                    </div>
                                `;

                            let cardHtml = `
                                <div class="col-xl-4 col-md-6 col-12">
                                    <div class="card h-100 issue-card">
                                        <div class="issue-card-header d-flex justify-content-between align-items-start">
                                            <div class="issue-card-title-wrap">
                                                <a href="${targetUrl}" class="issue-number-link" onclick="event.stopPropagation();">#${escapeHtml(item.issue_number || 'ABBL-IMS-000001')}</a>
                                                <span class="issue-card-pill">Issue</span>
                                                <div class="issue-card-meta-row">
                                                    <i class="ri-calendar-event-line"></i>
                                                    <span>เพิ่มเมื่อ ${escapeHtml(item.created_at_formatted || '-')}</span>
                                                </div>
                                            </div>
                                            <!-- 🎯 เพิ่มคุณสมบัติ data-bs-toggle="tooltip" เพื่อทำกล่องข้อความเวลาเมาส์ชี้ -->
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-outline-secondary btn-sm px-2 issue-view-btn" data-url="${targetUrl}" data-bs-toggle="tooltip" data-bs-placement="top" title="ดูรายละเอียด">
                                                    <i class="ri-eye-line"></i> 
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm px-2 issue-edit-btn" data-url="${editUrl}" data-bs-toggle="tooltip" data-bs-placement="top" title="แก้ไขข้อมูล">
                                                    <i class="ri-edit-line"></i> 
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body issue-card-body d-flex flex-column justify-content-between pt-2 pb-3" style="cursor: default;">
                                            <div class="mb-3">
                                                <h5 class="issue-card-title text-truncate">${escapeHtml(item.title_plain || 'ไม่มีหัวข้อ')}</h5>
                                                <p class="issue-card-description text-truncate-2-lines">${escapeHtml(item.description || 'รายละเอียดปัญหา...')}</p>
                                            </div>
                                            
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-dashed" style="border-color: #ebebeb !important;">
                                                    <span class="small text-muted"><i class="ri-alert-line me-1" style="color: ${priorityBorderColor};"></i> ระดับความเร่งด่วน:</span>
                                                    <span class="badge rounded px-2 py-1" style="background-color: ${priorityBg}; color: ${priorityTextColor}; border: 1px solid ${priorityBorderColor}40; font-size: 0.75rem; font-weight: 600;">
                                                        ${priorityText}
                                                    </span>
                                                </div>

                                                <div class="d-flex align-items-center gap-1 mb-2 pb-2 border-bottom border-dashed" style="border-color: #f1f1f1 !important;">
                                                    <span class="small text-muted">ผู้รับผิดชอบ:</span>
                                                    <span class="small">${assigneeHtml}</span>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="small text-muted">สถานะ:</span> 
                                                        <span class="badge rounded-pill px-2 py-1" style="${statusBg} font-size: 0.75rem; font-weight: 500;">
                                                            ${statusLabel}
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="position-relative d-flex justify-content-between align-items-center my-3 mx-1" style="height: 20px;">
                                                    <div class="position-absolute start-0 end-0" style="height: 4px; background-color: #e9ecef; top: 50%; transform: translateY(-50%); z-index: 1; border-radius: 2px;"></div>
                                                    <div class="position-absolute start-0" style="height: 4px; width: ${linePercent}%; background-color: ${progressColor}; top: 50%; transform: translateY(-50%); z-index: 2; transition: width 0.4s ease; border-radius: 2px;"></div>
                                                    
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 0 ? progressColor : '#fff'}; border-color: ${stepIndex >= 0 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 1 ? progressColor : '#fff'}; border-color: ${stepIndex >= 1 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 2 ? progressColor : '#fff'}; border-color: ${stepIndex >= 2 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 3 ? progressColor : '#fff'}; border-color: ${stepIndex >= 3 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                </div>

                                                ${commentPreviewHtml}

                                                <div class="issue-card-footer">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm issue-comment-btn" data-issue-id="${item.id}" data-view-url="${targetUrl}">
                                                        <i class="ri-chat-1-line me-1"></i> Comment <span class="fw-bold text-primary">(${commentCount})</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            container.append(cardHtml);
                        });

                        // 🎯 เปิดเรียกใช้งาน Tooltip หลังจากสร้าง HTML ของการ์ดเสร็จสิ้น
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl)
                        });

                        renderPagination(totalItems);
                    }
                    $("#showCards").show();
                },
                error: function() {
                    $("#showLoading").hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดข้อมูล Issue ได้'
                    });
                }
            });
        }

        function renderPagination(totalItems) {
            let totalPages = Math.ceil(totalItems / itemsPerPage);
            let paginationContainer = $("#issuePaginationContainer");
            paginationContainer.empty();

            if (totalPages <= 1) return;

            const maxButtons = 5;
            let currentGroup = Math.ceil(currentPage / maxButtons);
            let startPage = ((currentGroup - 1) * maxButtons) + 1;
            let endPage = Math.min(startPage + maxButtons - 1, totalPages);

            let prevDisabled = currentPage === 1 ? 'disabled style="background-color: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb;"' : 'style="background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db;"';
            paginationContainer.append(`
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <button class="page-link px-3 py-1.5" ${prevDisabled} onclick="changePage(${currentPage - 1})">&lt;</button>
                </li>
            `);

            if (startPage > 1) {
                paginationContainer.append(`
                    <li class="page-item">
                        <button class="page-link px-3 py-1.5" style="background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db;" 
                                title="ย้อนกลับเซ็ตก่อนหน้า" onclick="changePage(${startPage - 1})">...</button>
                    </li>
                `);
            }

            for (let i = startPage; i <= endPage; i++) {
                let btnStyle = i === currentPage 
                    ? 'style="background-color: #1f2937; color: #fff; border: 1px solid #1f2937; font-weight: bold;"' 
                    : 'style="background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db;"';
                
                paginationContainer.append(`
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <button class="page-link px-3 py-1.5" ${btnStyle} onclick="changePage(${i})">${i}</button>
                    </li>
                `);
            }

            if (endPage < totalPages) {
                paginationContainer.append(`
                    <li class="page-item">
                        <button class="page-link px-3 py-1.5" style="background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db;" 
                                title="ไปเซ็ตถัดไป" onclick="changePage(${endPage + 1})">...</button>
                    </li>
                `);
            }

            let nextDisabled = currentPage === totalPages ? 'disabled style="background-color: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb;"' : 'style="background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db;"';
            paginationContainer.append(`
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <button class="page-link px-3 py-1.5" ${nextDisabled} onclick="changePage(${currentPage + 1})">&gt;</button>
                </li>
            `);
        }

        function changePage(pageNumber) {
            currentPage = pageNumber;
            $("#showLoading").show();
            $("#showCards").hide();
            fetchCardData();
        }

        function reloadCards() {
            currentPage = 1; 
            $("#showLoading").show();
            $("#showCards").hide();
            fetchCardData();
        }

        function resetFilter() {
            $("#qt_wording").val('');
            $("#qt_status").val('').trigger('change');
            $('#qt_priority').val('');
            $('.btn-priority-filter').removeClass('active');
            reloadCards();
        }
    </script>

    {{-- มอดอล์สำหรับ "ดูรายละเอียด" --}}
    <div class="modal fade" id="issueViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="ri-eye-line me-1 text-secondary"></i> ดูรายละเอียด Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-3 text-muted">คุณต้องการเปิดหน้าต่างใหม่เพื่อตรวจสอบประวัติ ข้อมูลทั่วไป และรายละเอียดเชิงลึกของ Issue นี้หรือไม่?</p>
                    <div class="d-grid">
                        <a href="#" id="issueViewLink" class="btn btn-secondary">เปิดหน้าดูรายละเอียด</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- มอดอล์สำหรับ "แก้ไขข้อมูล" --}}
    <div class="modal fade" id="issueEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="ri-edit-line me-1"></i> แก้ไขข้อมูล Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-3 text-muted">คุณต้องการเข้าสู่หน้าแก้ไขข้อมูล เพื่อปรับเปลี่ยนรายละเอียด สถานะ หรือผู้รับผิดชอบของ Issue นี้ใช่หรือไม่?</p>
                    <div class="d-grid">
                        <a href="#" id="issueEditLink" class="btn btn-primary">เข้าสู่หน้าแก้ไขข้อมูล</a>
                    </div>
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

@section('style')
    <style>
        .pagination .page-link {
            border-radius: 4px !important;
            box-shadow: none !important;
            transition: all 0.2s ease;
        }
        .pagination .page-link:hover:not([disabled]) {
            background-color: #d1d5db !important;
            color: #1f2937 !important;
        }
        
        .btn-priority-filter.active {
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }
        .btn-outline-danger.btn-priority-filter.active {
            background-color: #dc3545 !important;
            color: #fff !important;
            border-color: #dc3545 !important;
        }
        .btn-outline-warning.btn-priority-filter.active {
            background-color: #ffc107 !important;
            color: #fff !important;
            border-color: #ffc107 !important;
        }
        .btn-outline-success.btn-priority-filter.active {
            background-color: #198754 !important;
            color: #fff !important;
            border-color: #198754 !important;
        }
        .btn-outline-danger.btn-priority-filter {
            background-color: rgba(220,53,69,0.06);
            color: #842029;
            border-color: rgba(220,53,69,0.18);
        }
        .btn-outline-warning.btn-priority-filter {
            background-color: rgba(255,193,7,0.06);
            color: #7a4f01;
            border-color: rgba(255,193,7,0.18);
        }
        .btn-outline-success.btn-priority-filter {
            background-color: rgba(25,135,84,0.06);
            color: #0b5e3b;
            border-color: rgba(25,135,84,0.18);
        }
        .btn-priority-filter.active {
            color: #fff !important;
        }
        .btn-priority-filter {
            box-shadow: none !important;
            transition: transform .12s ease, box-shadow .12s ease;
        }
        .btn-priority-filter i {
            margin-right: .25rem;
        }
        .btn-priority-filter:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06) !important;
        }
    </style>
@endsection