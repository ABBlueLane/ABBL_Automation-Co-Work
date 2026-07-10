@extends('layouts.office')
@section('title', 'OneClick | Issue Management System')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0"><i class="ri-bug-line me-1"></i>
                    Issue Management System</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            Issue Management
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm" data-aos="fade-left">
                {{-- Header --}}
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        รายการ Issue
                    </h4>
                    <a href="{{ officeBusinessRoute('issue.project.index') }}" class="btn btn-soft-primary btn-sm">
                        <i class="ri-folder-line me-1"></i> จัดการโปรเจค
                    </a>
                </div>
                <div class="card-body">
                    {{-- Filter Section --}}
                    <div class="border rounded p-3 mb-4 bg-light">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <label class="form-label">
                                    <i class="ri-search-line me-1"></i>
                                    คำค้นหา
                                </label>
                                <input type="text" class="form-control" id="qt_wording" placeholder="พิมพ์คำค้นหา...">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">
                                    <i class="ri-filter-3-line me-1"></i>
                                    สถานะ
                                </label>
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
                            <div class="col-lg-3">
                                <label class="form-label">
                                    <i class="ri-alarm-warning-line me-1"></i>
                                    ความเร่งด่วน
                                </label>
                                <select id="qt_priority" class="select">
                                    <option value="">ทั้งหมด</option>
                                    @foreach (\App\Models\Issue::getPriorityOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">
                                    <i class="ri-folder-line me-1"></i>
                                    โปรเจค
                                </label>
                                <select id="qt_issue_project" class="select">
                                    <option value="">ทั้งหมด</option>
                                    @foreach ($issueProjects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">
                                    <i class="ri-user-line me-1"></i>
                                    ผู้รับผิดชอบ
                                </label>
                                <select id="qt_assigned_to" class="select">
                                    <option value="">ทั้งหมด</option>
                                    <option value="null">ยังไม่มอบหมาย</option>
                                    @foreach ($staffs as $staff)
                                        <option value="{{ $staff->id }}">
                                            {{ $staff->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 d-flex align-items-end gap-2">
                                <button class="btn btn-primary" type="button" onclick="reloadTable();">
                                    <i class="ri-search-line"></i>
                                    ค้นหา
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="resetFilter();">
                                    <i class="ri-refresh-line"></i>
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- Loading --}}
                    <div class="text-center py-5" id="showLoading">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="text-muted">
                            กำลังโหลดข้อมูล...
                        </div>
                    </div>
                    {{-- Table --}}
                    <div class="table-responsive" id="showTable" style="display:none;">
                        <table class="table table-hover table-striped w-100" id="officeIssueTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>No.</th>
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
            </div>
        </div>
    </div>
    <div id="assignModalContainer"></div>

@endsection
@section('script')
    <script>
        let officeIssueTable = null;
        const assignModalUrlTemplate = "{{ officeBusinessRoute('issue.assign.modal', ['id' => '__ID__']) }}";
        const assignSubmitUrlTemplate = "{{ officeBusinessRoute('issue.assign', ['id' => '__ID__']) }}";

        function applyQueryFiltersFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const wording = params.get('wording');
            const status = params.get('status');
            const priority = params.get('priority');
            const issueProjectId = params.get('issue_project_id');
            const assignedTo = params.get('assigned_to');
            if (wording) {
                $("#qt_wording").val(wording);
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
            $(".select").not("#qt_assigned_to").select2({
                width: "100%",
                minimumResultsForSearch: -1
            });
            $("#qt_assigned_to").select2({
                width: "100%"
            });
            applyQueryFiltersFromUrl();
            $("#qt_status, #qt_priority, #qt_issue_project, #qt_assigned_to").trigger("change");
            initTable();
            $("#qt_wording").keypress(function(e) {
                if (e.which == 13) {
                    reloadTable();
                }
            });
            $("#qt_status").on('change', reloadTable);
            $("#qt_priority").on('change', reloadTable);
            $("#qt_issue_project").on('change', reloadTable);
            $("#qt_assigned_to").on('change', reloadTable);
        });

        function initTable() {
            officeIssueTable = new DataTable('#officeIssueTable', {
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 100,
                ajax: {
                    url: "{{ officeBusinessRoute('issue.table') }}",
                    type: "GET",
                    data: function(d) {
                        d.wording = $("#qt_wording").val();
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
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'issue_number',
                        name: 'issue_number'
                    },
                    {
                        data: 'issue_project_html',
                        name: 'issue_project_id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title_html',
                        name: 'title'
                    },
                    {
                        data: 'status_html',
                        name: 'status'
                    },
                    {
                        data: 'priority_html',
                        name: 'priority'
                    },
                    {
                        data: 'planned_start_at_html',
                        name: 'planned_start_at',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'due_at_html',
                        name: 'due_at',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'schedule_status_html',
                        name: 'schedule_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_elapsed_html',
                        name: 'created_elapsed',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'last_action_at_html',
                        name: 'last_action_at',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'assigned_to_html',
                        name: 'assigned_to',
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
            if (officeIssueTable) {
                officeIssueTable.ajax.reload(null, false);
            }
        }

        function resetFilter() {
            $("#qt_wording").val('');
            $("#qt_status").val('').trigger('change');
            $("#qt_priority").val('').trigger('change');
            $("#qt_issue_project").val('').trigger('change');
            $("#qt_assigned_to").val('').trigger('change');
            reloadTable();
        }
        function openAssignModal(issueId) {
            $.ajax({
                url: assignModalUrlTemplate.replace('__ID__', issueId),
                type: "GET",
                success: function(html) {
                    $("#assignModalContainer").html(html);
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
            const submitUrl = assignSubmitUrlTemplate.replace('__ID__', issueId);

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
