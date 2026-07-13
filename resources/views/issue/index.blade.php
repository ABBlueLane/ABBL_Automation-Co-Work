@extends('api_clients.layout')

@section('title', 'Issue Management')

@push('styles')
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .issue-filters {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .issue-filters label {
            margin-bottom: .35rem;
            font-size: .875rem;
        }

        .issue-filters .filter-actions {
            display: flex;
            align-items: flex-end;
            gap: .5rem;
        }

        #showLoading {
            text-align: center;
            padding: 2rem 0;
            color: var(--vz-secondary-color);
        }

        #showTable {
            overflow-x: auto;
        }

        #adminIssueTable {
            width: 100% !important;
        }

        #adminIssueTable th {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="topbar">
        <div>
            <h1>Issue Management</h1>
            <p>รายการ Issue ทั้งหมดในระบบ IMS</p>
        </div>
    </div>

    <div class="panel">
        <div class="issue-filters">
            <div>
                <label for="qt_wording">คำค้นหา</label>
                <input type="text" class="form-control" id="qt_wording" placeholder="พิมพ์คำค้นหา...">
            </div>
            <div>
                <label for="qt_business_id">ธุรกิจ</label>
                <select id="qt_business_id" class="select">
                    <option value="">ทั้งหมด</option>
                    @foreach ($businesses as $business)
                        <option value="{{ $business->id }}">{{ $business->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="qt_status">สถานะ</label>
                <select id="qt_status" class="select">
                    <option value="">ทั้งหมด</option>
                    <option value="open">ยังไม่ปิดงาน (Open)</option>
                    <option value="pending">รอรีวิว</option>
                    <option value="in_progress">กำลังดำเนินการ</option>
                    <option value="waiting_review">รอตรวจ</option>
                    <option value="customer_replied">ลูกค้าตอบกลับแล้วรอทีมงานดำเนินการ</option>
                    <option value="done">ดำเนินการแล้ว</option>
                </select>
            </div>
            <div>
                <label for="qt_priority">ความเร่งด่วน</label>
                <select id="qt_priority" class="select">
                    <option value="">ทั้งหมด</option>
                    @foreach (\App\Models\Issue::getPriorityOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="qt_issue_project">โปรเจค</label>
                <select id="qt_issue_project" class="select">
                    <option value="">ทั้งหมด</option>
                    @foreach ($issueProjects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="qt_assigned_to">ผู้รับผิดชอบ</label>
                <select id="qt_assigned_to" class="select">
                    <option value="">ทั้งหมด</option>
                    <option value="null">ยังไม่มอบหมาย</option>
                    @foreach ($staffs as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button class="button" type="button" onclick="reloadTable();">ค้นหา</button>
                <button class="button secondary" type="button" onclick="resetFilter();">Reset</button>
            </div>
        </div>

        <div id="showLoading">
            กำลังโหลดข้อมูล...
        </div>

        <div id="showTable" style="display:none;">
            <table class="table table-hover" id="adminIssueTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>No.</th>
                        <th>ธุรกิจ</th>
                        <th>โปรเจค</th>
                        <th>เรื่อง</th>
                        <th>สถานะ</th>
                        <th>ความเร่งด่วน</th>
                        <th>เริ่ม</th>
                        <th>กำหนดเสร็จ</th>
                        <th>สถานะเวลา</th>
                        <th>สร้างเมื่อ / เปิดมา</th>
                        <th>ดำเนินการล่าสุด</th>
                        <th>ผู้รับผิดชอบ</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div id="assignModalContainer"></div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let adminIssueTable = null;
        const assignModalUrlTemplate = @json(route('office.issue.assign.modal', ['business' => '__BUSINESS__', 'id' => '__ID__']));
        const assignSubmitUrlTemplate = @json(route('office.issue.assign', ['business' => '__BUSINESS__', 'id' => '__ID__']));

        function applyQueryFiltersFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const wording = params.get('wording');
            const businessId = params.get('business_id');
            const status = params.get('status');
            const priority = params.get('priority');
            const issueProjectId = params.get('issue_project_id');
            const assignedTo = params.get('assigned_to');

            if (wording) {
                $("#qt_wording").val(wording);
            }
            if (businessId) {
                $("#qt_business_id").val(businessId);
            }
            if (status) {
                $("#qt_status").val(status);
            }
            if (priority) {
                $("#qt_priority").val(priority);
            }
            if (issueProjectId) {
                $("#qt_issue_project").val(issueProjectId);
            }
            if (assignedTo) {
                $("#qt_assigned_to").val(assignedTo);
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
            initTable();
            $("#qt_wording").keypress(function(e) {
                if (e.which == 13) {
                    reloadTable();
                }
            });
            $("#qt_status, #qt_priority, #qt_issue_project, #qt_assigned_to, #qt_business_id").on('change', reloadTable);
        });

        function initTable() {
            adminIssueTable = new DataTable('#adminIssueTable', {
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 100,
                ajax: {
                    url: @json(route('admin.issues.table')),
                    type: "GET",
                    data: function(d) {
                        d.wording = $("#qt_wording").val();
                        d.business_id = $("#qt_business_id").val();
                        d.status = $("#qt_status").val();
                        d.priority = $("#qt_priority").val();
                        d.issue_project_id = $("#qt_issue_project").val();
                        d.assigned_to = $("#qt_assigned_to").val();
                    },
                    error: function() {
                        $("#showLoading").hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'เกิดข้อผิดพลาดจากระบบ'
                        });
                    }
                },
                order: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'issue_number'
                    },
                    {
                        data: 'business_html',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'issue_project_html',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title_html'
                    },
                    {
                        data: 'status_html'
                    },
                    {
                        data: 'priority_html'
                    },
                    {
                        data: 'planned_start_at_html',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'due_at_html',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'schedule_status_html',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_elapsed_html',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'last_action_at_html',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'assigned_to_html',
                        orderable: false,
                        searchable: false
                    }
                ],
                drawCallback: function() {
                    $("#showLoading").hide();
                    $("#showTable").show();
                }
            });
        }

        function reloadTable() {
            $("#showLoading").show();
            $("#showTable").hide();
            if (adminIssueTable) {
                adminIssueTable.ajax.reload(null, false);
            }
        }

        function resetFilter() {
            $("#qt_wording").val('');
            $("#qt_business_id").val('').trigger('change');
            $("#qt_status").val('').trigger('change');
            $("#qt_priority").val('').trigger('change');
            $("#qt_issue_project").val('').trigger('change');
            $("#qt_assigned_to").val('').trigger('change');
            reloadTable();
        }

        function openAssignModal(issueId, businessId) {
            $.ajax({
                url: assignModalUrlTemplate.replace('__BUSINESS__', businessId).replace('__ID__', issueId),
                type: "GET",
                success: function(html) {
                    $("#assignModalContainer").html(html);
                    $("#assignIssueForm").data('business-id', businessId);
                    $("#issueAssignModal").modal("show");
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'ไม่สามารถเปิดหน้าต่างมอบหมายงานได้'
                    });
                }
            });
        }

        $(document).on('submit', '#assignIssueForm', function(e) {
            e.preventDefault();

            const form = $(this);
            const issueId = form.find('input[name="issue_id"]').val();
            const businessId = form.data('business-id');
            const submitUrl = assignSubmitUrlTemplate.replace('__BUSINESS__', businessId).replace('__ID__', issueId);

            $.ajax({
                url: submitUrl,
                type: "POST",
                data: form.serialize(),
                success: function(res) {
                    if (res.success) {
                        $("#issueAssignModal").modal("hide");
                        reloadTable();
                        Swal.fire({
                            icon: 'success',
                            title: 'มอบหมายสำเร็จ',
                            text: 'ระบบอัปเดตผู้รับผิดชอบและแผนเวลาแล้ว',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const firstError = Object.values(xhr.responseJSON.errors)[0][0];
                        Swal.fire({
                            icon: 'warning',
                            title: 'ข้อมูลไม่ถูกต้อง',
                            text: firstError
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'เกิดข้อผิดพลาดจากระบบ'
                    });
                }
            });
        });
    </script>
@endsection
