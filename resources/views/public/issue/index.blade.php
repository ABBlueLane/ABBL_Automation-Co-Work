@extends('layouts.public')
@section('title', 'OneClick | ผู้ใช้งานระบบ')

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
                <div class="border rounded p-3 mb-3 bg-white shadow-sm">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label fw-bold text-muted small">
                                <i class="ri-search-line me-1"></i> คำค้นหา
                            </label>
                            <input type="text" class="form-control" id="qt_wording" placeholder="พิมพ์คำค้นหา...">
                        </div>
                        <div class="col-lg-3">
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
                        <div class="col-lg-3 d-flex align-items-end gap-2">
                            <button class="btn btn-primary btn-sm rounded d-flex align-items-center gap-1" type="button" onclick="reloadCards();">
                                <i class="ri-search-line"></i> ค้นหา
                            </button>
                            <button class="btn btn-outline-secondary btn-sm rounded d-flex align-items-center gap-1" type="button" onclick="resetFilter();">
                                <i class="ri-refresh-line"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ปุ่มฟิลเตอร์ระดับความเร่งด่วน 3 ปุ่มชิดขวาเหนือการ์ด --}}
                <div class="d-flex justify-content-end gap-2 mb-4 pt-2 flex-wrap">
                    <input type="hidden" id="qt_priority" value="">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-priority-filter" data-priority="high">
                        <i class="ri-alert-fill"></i> ด่วน
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm btn-priority-filter" data-priority="medium">
                        <i class="ri-alert-line"></i> กลาง
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm btn-priority-filter" data-priority="low">
                        <i class="ri-checkbox-blank-circle-line"></i> น้อย
                    </button>
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
        });

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

                            // ==========================================
                            // คำนวณลำดับจุดสเต็ป (Step Index) และ % การวิ่งของเส้นสถานะแบบยืดหยุ่นคำ
                            // ==========================================
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
                                    label: 'ลูกค้าตอบกลับ',
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
                            let displayPercent = statusInfo.displayPercent;

                            // คำนวณสีตามเปอร์เซ็นต์ความสำเร็จ
                            let progressColor = '#dc3545'; 
                            if (displayPercent === 50 || displayPercent === 75) {
                                progressColor = '#ffc107'; 
                            } else if (displayPercent === 100) {
                                progressColor = '#198754'; 
                            }

                            let targetUrl = item.view_url || '#';

                            let cardHtml = `
                                <div class="col-xl-4 col-md-6 col-12">
                                    <div class="card h-100 border shadow-sm" style="border-radius: 8px; border-bottom: none !important;">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 border-bottom-0" style="border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                            <span class="small">
                                                #<a href="${targetUrl}" class="fw-bold text-primary text-decoration-underline" onclick="event.stopPropagation();">${item.issue_number || 'ABBL-IMS-000001'}</a>
                                            </span>
                                            <span class="small text-muted">ผู้รับผิดชอบ : <strong class="text-dark">${item.assigned_to_html || '-'}</strong></span>
                                        </div>
                                        
                                        <div class="card-body d-flex flex-column justify-content-between pt-2 pb-3" onclick="window.location.href='${targetUrl}';" style="cursor: pointer;">
                                            <div class="mb-3">
                                                <h5 class="card-title text-truncate mb-1 fw-bold" style="font-size: 1.1rem;">
                                                    <span class="text-primary text-decoration-underline">${item.title_plain || 'ไม่มีหัวข้อ'}</span>
                                                </h5>
                                                <p class="card-text text-muted small text-truncate-2-lines mb-0" style="font-size: 0.875rem;">
                                                    ${item.description || 'รายละเอียดปัญหา...'}
                                                </p>
                                            </div>
                                            
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-dashed" style="border-color: #ebebeb !important;">
                                                    <span class="small text-muted"><i class="ri-alert-line me-1" style="color: ${priorityBorderColor};"></i> ระดับความเร่งด่วน:</span>
                                                    <span class="badge rounded px-2 py-1" style="background-color: ${priorityBg}; color: ${priorityTextColor}; border: 1px solid ${priorityBorderColor}40; font-size: 0.75rem; font-weight: 600;">
                                                        ${priorityText}
                                                    </span>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="small text-muted">สถานะ:</span> 
                                                        <span class="badge rounded-pill px-2 py-1" style="${statusBg} font-size: 0.75rem; font-weight: 500;">
                                                            ${statusLabel}
                                                        </span>
                                                    </div>
                                                    <span class="small fw-bold" style="color: ${progressColor}; font-size: 0.9rem;">${displayPercent}%</span>
                                                </div>
                                                
                                                <div class="position-relative d-flex justify-content-between align-items-center my-3 mx-1" style="height: 20px;">
                                                    <div class="position-absolute start-0 end-0" style="height: 4px; background-color: #e9ecef; top: 50%; transform: translateY(-50%); z-index: 1; border-radius: 2px;"></div>
                                                    <div class="position-absolute start-0" style="height: 4px; width: ${linePercent}%; background-color: ${progressColor}; top: 50%; transform: translateY(-50%); z-index: 2; transition: width 0.4s ease; border-radius: 2px;"></div>
                                                    
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 0 ? progressColor : '#fff'}; border-color: ${stepIndex >= 0 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 1 ? progressColor : '#fff'}; border-color: ${stepIndex >= 1 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 2 ? progressColor : '#fff'}; border-color: ${stepIndex >= 2 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                    <div class="rounded-circle border" style="width: 12px; height: 12px; background-color: ${stepIndex >= 3 ? progressColor : '#fff'}; border-color: ${stepIndex >= 3 ? progressColor : '#ced4da'} !important; z-index: 3; transition: all 0.3s ease;"></div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: #f1f1f1 !important;">
                                                    <span class="text-muted small">
                                                        <i class="ri-calendar-event-line me-1"></i>${item.created_at_formatted || '-'}
                                                    </span>
                                                    <a href="${targetUrl}" class="text-muted small text-decoration-none d-flex align-items-center gap-1" onclick="event.stopPropagation();" style="cursor: pointer;">
                                                        <i class="ri-chat-1-line me-1 text-primary"></i> 
                                                        <span>จำนวนข้อความ:</span>
                                                        <span class="text-dark fw-bold">${item.comments_count || 0}</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            container.append(cardHtml);
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
            font-size: .95rem;
            padding: .28rem .9rem;
        }
        .btn-outline-warning.btn-priority-filter {
            background-color: rgba(255,193,7,0.06);
            color: #7a4f01;
            border-color: rgba(255,193,7,0.18);
            font-size: .95rem;
            padding: .28rem .9rem;
        }
        .btn-outline-success.btn-priority-filter {
            background-color: rgba(25,135,84,0.06);
            color: #0b5e3b;
            border-color: rgba(25,135,84,0.18);
            font-size: .95rem;
            padding: .28rem .9rem;
        }
        .btn-priority-filter.active {
            color: #fff !important;
        }
        .btn-primary.btn-sm, .btn-outline-secondary.btn-sm {
            font-size: .95rem;
            padding: .35rem .9rem;
        }
        .btn-priority-filter{
            border-radius: 999px;
            padding: .35rem .85rem;
            box-shadow: none !important;
            transition: transform .12s ease, box-shadow .12s ease;
        }
        .btn-priority-filter i{
            margin-right: .4rem;
        }
        .btn-priority-filter:hover{
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06) !important;
        }
        .btn-primary.rounded-pill{
            box-shadow: 0 4px 10px rgba(15,23,42,0.06);
        }
    </style>
@endsection