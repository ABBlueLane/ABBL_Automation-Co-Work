<script>
    let currentPage = 1;
    const itemsPerPage = 12;
    let totalFilteredItems = 0;
    let activeIssueId = null;
    let activeBusinessId = null;
    let searchDebounceTimer = null;
    const tableUrl = @json($tableUrl);
    const commentsUrlTemplate = @json($commentsUrlTemplate);
    const commentStoreUrlTemplate = @json($commentStoreUrlTemplate);
    const showBusinessBadge = @json($showBusinessBadge ?? false);
    const csrfToken = @json(csrf_token());

    const progressSteps = [
        { key: 'pending', label: 'รอรีวิว' },
        { key: 'in_progress', label: 'กำลังดำเนินการ' },
        { key: 'waiting_review', label: 'รอตรวจ' },
        { key: 'customer_replied', label: 'ลูกค้าตอบกลับ' },
        { key: 'done', label: 'เสร็จสิ้น' },
    ];

    const statusStepMap = {
        draft: 0,
        pending: 0,
        in_progress: 1,
        waiting_review: 2,
        customer_replied: 3,
        done: 4,
    };

    const progressColors = {
        pending: '#dc2626',
        in_progress: '#ea580c',
        waiting_review: '#7c3aed',
        customer_replied: '#2563eb',
        done: '#16a34a',
    };

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

    function getFilterPayload() {
        return {
            wording: $("#qt_wording").val(),
            status: $("#qt_status").val(),
            priority: $("#qt_priority").val(),
            business_id: $("#qt_business_id").val(),
            issue_project_id: $("#qt_issue_project").val(),
            assigned_to: $("#qt_assigned_to").val(),
            draw: 1,
            start: (currentPage - 1) * itemsPerPage,
            length: itemsPerPage
        };
    }

    function setPriorityPillActive(priority) {
        $('.btn-priority-filter').removeClass('active');
        const selector = priority
            ? `.btn-priority-filter[data-priority="${priority}"]`
            : '.btn-priority-filter[data-priority=""]';
        $(selector).addClass('active');
    }

    function applyQueryFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const mappings = {
            wording: '#qt_wording',
            business_id: '#qt_business_id',
            status: '#qt_status',
            priority: '#qt_priority',
            issue_project_id: '#qt_issue_project',
            assigned_to: '#qt_assigned_to',
        };

        let hasAdvanced = false;

        Object.entries(mappings).forEach(([key, selector]) => {
            const value = params.get(key);
            if (value !== null && value !== '') {
                $(selector).val(value);
                if (['business_id', 'status', 'issue_project_id', 'assigned_to'].includes(key)) {
                    hasAdvanced = true;
                }
            }
        });

        setPriorityPillActive(params.get('priority') || '');

        if (hasAdvanced) {
            $('#advancedFilters').prop('hidden', false);
            $('#toggleAdvancedFilters').attr('aria-expanded', 'true');
        }
    }

    $(document).ready(function() {
        $(".select").not("#qt_assigned_to, #qt_business_id").select2({
            width: "100%",
            minimumResultsForSearch: -1
        });
        $("#qt_assigned_to, #qt_business_id").select2({
            width: "100%"
        });

        applyQueryFiltersFromUrl();
        $("#qt_status, #qt_priority, #qt_issue_project, #qt_assigned_to, #qt_business_id").trigger("change");
        fetchCardData();

        $("#qt_wording").on('input', function() {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(reloadCards, 400);
        });

        $("#qt_wording").keypress(function(e) {
            if (e.which == 13) {
                clearTimeout(searchDebounceTimer);
                reloadCards();
            }
        });

        $("#qt_status, #qt_issue_project, #qt_assigned_to, #qt_business_id").on('change select2:select select2:unselect', reloadCards);

        $('.btn-priority-filter').on('click', function() {
            const clickedPriority = $(this).data('priority');
            const currentPriority = $('#qt_priority').val();

            if (currentPriority === clickedPriority) {
                return;
            }

            $('#qt_priority').val(clickedPriority);
            setPriorityPillActive(clickedPriority);
            reloadCards();
        });

        $('#toggleAdvancedFilters').on('click', function() {
            const panel = $('#advancedFilters');
            const expanded = !panel.prop('hidden');
            panel.prop('hidden', expanded);
            $(this).attr('aria-expanded', expanded ? 'false' : 'true');
        });

        $(document).on('click', '.issue-card', function(e) {
            if ($(e.target).closest('.issue-card-comments, .issue-card-arrow, .issue-card-id a').length) {
                return;
            }
            const url = $(this).data('view-url');
            if (url && url !== '#') {
                window.location.href = url;
            }
        });

        $(document).on('click', '.issue-card-arrow', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const url = $(this).attr('href');
            if (url && url !== '#') {
                window.location.href = url;
            }
        });

        $(document).on('click', '.issue-card-comments', function(e) {
            e.preventDefault();
            e.stopPropagation();
            activeIssueId = $(this).data('issue-id');
            activeBusinessId = $(this).data('business-id') || '';
            $('#commentBusinessId').val(activeBusinessId);
            $('#commentList').html('<div class="issue-comment-empty">กำลังโหลดความคิดเห็น...</div>');
            $('#commentInput').val('');
            $('#issueCommentModal').modal('show');
            loadComments(activeIssueId, activeBusinessId);
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
                    business_id: activeBusinessId,
                    _token: csrfToken
                },
                success: function() {
                    $('#commentInput').val('');
                    loadComments(activeIssueId, activeBusinessId);
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

    function loadComments(issueId, businessId) {
        if (!issueId) {
            return;
        }

        const url = commentsUrlTemplate.replace('__ISSUE_ID__', issueId);
        $.ajax({
            url: url,
            type: 'GET',
            data: { business_id: businessId },
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
        $.ajax({
            url: tableUrl,
            type: "GET",
            data: getFilterPayload(),
            success: function(response) {
                $("#showLoading").hide();
                const container = $("#issueCardContainer");
                container.empty();

                const items = response.data || [];
                totalFilteredItems = response.recordsFiltered !== undefined ? response.recordsFiltered : items.length;

                if (items.length === 0) {
                    container.append(`
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="ri-inbox-line ri-2x mb-2 d-block"></i>
                            ไม่พบข้อมูลรายการ Issue
                        </div>
                    `);
                    $("#issuePaginationContainer").empty();
                    $("#issuePaginationInfo").text('');
                } else {
                    items.forEach(function(item) {
                        container.append(buildIssueCardHtml(item));
                    });
                    renderPagination(totalFilteredItems);
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

    function getPriorityMeta(priority) {
        const value = String(priority || 'medium').toLowerCase();
        if (value === 'high' || value.includes('ด่วน')) {
            return { class: 'priority-high', label: 'HIGH' };
        }
        if (value === 'low' || value.includes('น้อย')) {
            return { class: 'priority-low', label: 'LOW' };
        }
        return { class: 'priority-medium', label: 'MEDIUM' };
    }

    function formatDisplayDate(dateStr) {
        if (!dateStr) {
            return '-';
        }
        const parts = dateStr.split(' ');
        const datePart = parts[0] || '';
        const segments = datePart.split('/');
        if (segments.length === 3) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const day = parseInt(segments[0], 10);
            const month = months[parseInt(segments[1], 10) - 1] || segments[1];
            const year = segments[2];
            return `${day} ${month} ${year}`;
        }
        return dateStr;
    }

    function buildProgressHtml(status) {
        const currentStatus = (status || 'pending').toString().toLowerCase().trim();
        const currentStep = statusStepMap[currentStatus] ?? 0;
        const color = progressColors[currentStatus] || progressColors.pending;
        const fillPercent = currentStep === 0 ? 0 : (currentStep / (progressSteps.length - 1)) * 100;

        let stepsHtml = '';
        progressSteps.forEach(function(step, index) {
            const isDone = index <= currentStep;
            const isActive = index === currentStep;
            stepsHtml += `
                <div class="issue-progress-step">
                    <div class="issue-progress-dot ${isDone ? 'done' : ''}" style="${isDone ? `background:${color};border-color:${color};` : ''}"></div>
                    <span class="issue-progress-step-label ${isActive ? 'active' : ''}">${step.label}</span>
                </div>
            `;
        });

        const fillStyle = fillPercent > 0
            ? `width: ${fillPercent}%; max-width: calc(100% - 16px); background-color: ${color};`
            : 'width: 0;';

        return `
            <span class="issue-card-progress-label">Progress</span>
            <div class="issue-progress-track">
                <div class="issue-progress-line-bg"></div>
                <div class="issue-progress-line-fill" style="${fillStyle}"></div>
                ${stepsHtml}
            </div>
        `;
    }

    function buildIssueCardHtml(item) {
        const priorityMeta = getPriorityMeta(item.priority);
        const targetUrl = item.view_url || '#';
        const commentCount = item.comments_count !== undefined ? parseInt(item.comments_count, 10) : 0;
        const businessBadge = showBusinessBadge && item.business_name
            ? `<span class="issue-card-business">${escapeHtml(item.business_name)}</span>`
            : '';

        return `
            <div class="col-xl-4 col-md-6 col-12">
                <div class="issue-card" data-view-url="${targetUrl}">
                    <div class="issue-card-top">
                        <span class="issue-card-id">
                            <a href="${targetUrl}" onclick="event.stopPropagation();">#${escapeHtml(item.issue_number || '-')}</a>
                        </span>
                        <span class="issue-card-priority-badge ${priorityMeta.class}">${priorityMeta.label}</span>
                    </div>
                    ${businessBadge}
                    <h3 class="issue-card-title">${escapeHtml(item.title_plain || 'ไม่มีหัวข้อ')}</h3>
                    <p class="issue-card-description">${escapeHtml(item.description || 'รายละเอียดปัญหา...')}</p>
                    <div class="issue-card-progress">
                        ${buildProgressHtml(item.status)}
                    </div>
                    <div class="issue-card-footer">
                        <span class="issue-card-date">${escapeHtml(formatDisplayDate(item.created_at_formatted))}</span>
                        <button type="button" class="issue-card-comments" data-issue-id="${item.id}" data-business-id="${item.business_id || ''}">
                            <i class="ri-chat-1-line"></i> ${commentCount}
                        </button>
                        <a href="${targetUrl}" class="issue-card-arrow" onclick="event.stopPropagation();">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const paginationContainer = $("#issuePaginationContainer");
        const startItem = totalItems === 0 ? 0 : ((currentPage - 1) * itemsPerPage) + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);

        $("#issuePaginationInfo").text(`แสดง ${startItem}–${endItem} จาก ${totalItems} รายการ`);
        paginationContainer.empty();

        if (totalPages <= 1) {
            return;
        }

        const prevDisabled = currentPage === 1;
        paginationContainer.append(`
            <li class="page-item ${prevDisabled ? 'disabled' : ''}">
                <button class="page-link" ${prevDisabled ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
                    <i class="ri-arrow-left-s-line"></i> ก่อนหน้า
                </button>
            </li>
        `);

        const maxButtons = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        startPage = Math.max(1, endPage - maxButtons + 1);

        for (let i = startPage; i <= endPage; i++) {
            paginationContainer.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" onclick="changePage(${i})">${i}</button>
                </li>
            `);
        }

        const nextDisabled = currentPage === totalPages;
        paginationContainer.append(`
            <li class="page-item ${nextDisabled ? 'disabled' : ''}">
                <button class="page-link" ${nextDisabled ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
                    ถัดไป <i class="ri-arrow-right-s-line"></i>
                </button>
            </li>
        `);
    }

    function changePage(pageNumber) {
        const totalPages = Math.ceil(totalFilteredItems / itemsPerPage);
        if (pageNumber < 1 || pageNumber > totalPages) {
            return;
        }
        currentPage = pageNumber;
        $("#showLoading").show();
        $("#showCards").hide();
        fetchCardData();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function reloadCards() {
        currentPage = 1;
        $("#showLoading").show();
        $("#showCards").hide();
        fetchCardData();
    }

    function resetFilter() {
        $("#qt_wording").val('');
        $("#qt_business_id").val('').trigger('change');
        $("#qt_status").val('').trigger('change');
        $("#qt_issue_project").val('').trigger('change');
        $("#qt_assigned_to").val('').trigger('change');
        $('#qt_priority').val('');
        setPriorityPillActive('');
        reloadCards();
    }
</script>
