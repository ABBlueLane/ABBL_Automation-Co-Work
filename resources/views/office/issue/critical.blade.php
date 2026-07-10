@extends('layouts.office')
@section('title', 'OneClick | Critical Issue')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0"><i class="ri-alert-line me-1"></i> Critical Issue</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ officeBusinessRoute('issue.index') }}">Issue Management</a>
                        </li>
                        <li class="breadcrumb-item active">Critical Issue</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">ปัญหาใหญ่และวิธีแก้</h4>
                    <button type="button" class="btn btn-success btn-sm" onclick="openAddModal()">
                        <i class="ri-add-line me-1"></i> เพิ่มรายการ
                    </button>
                </div>
                <div class="card-body">
                    <div class="text-center py-5" id="showLoading">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="text-muted">กำลังโหลดข้อมูล...</div>
                    </div>

                    <div class="table-responsive" id="showTable" style="display:none;">
                        <table class="table table-hover table-striped w-100" id="criticalIssueTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th width="22%">ปัญหา</th>
                                    <th width="22%">วิธีแก้ไข</th>
                                    <th width="15%">เครื่องมือ</th>
                                    <th width="12%">ผู้บันทึก</th>
                                    <th width="12%">วันที่บันทึก</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="criticalIssueModalContainer"></div>
@endsection

@section('script')
    <script>
        let criticalIssueTable = null;
        const modalAddUrl = "{{ officeBusinessRoute('issue.critical.modal.add') }}";
        const modalEditUrlTemplate = "{{ officeBusinessRoute('issue.critical.modal.edit', ['id' => '__ID__']) }}";
        const storeUrl = "{{ officeBusinessRoute('issue.critical.store') }}";
        const updateUrlTemplate = "{{ officeBusinessRoute('issue.critical.update', ['id' => '__ID__']) }}";
        const destroyUrlTemplate = "{{ officeBusinessRoute('issue.critical.destroy', ['id' => '__ID__']) }}";
        const csrfToken = "{{ csrf_token() }}";

        $(document).ready(function() {
            initTable();
        });

        function initTable() {
            criticalIssueTable = new DataTable('#criticalIssueTable', {
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 50,
                ajax: {
                    url: "{{ officeBusinessRoute('issue.critical.table') }}",
                    type: "GET",
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
                    [5, 'desc']
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'problem',
                        name: 'problem'
                    },
                    {
                        data: 'solution',
                        name: 'solution'
                    },
                    {
                        data: 'tools',
                        name: 'tools'
                    },
                    {
                        data: 'created_by_name',
                        name: 'created_by',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
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
            if (criticalIssueTable) {
                criticalIssueTable.ajax.reload(null, false);
            }
        }

        function openAddModal() {
            $.ajax({
                url: modalAddUrl,
                type: "GET",
                success: function(html) {
                    $("#criticalIssueModalContainer").html(html);
                    $("#criticalIssueAddModal").modal("show");
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'ไม่สามารถเปิดหน้าต่างเพิ่มรายการได้'
                    });
                }
            });
        }

        function openEditModal(id) {
            $.ajax({
                url: modalEditUrlTemplate.replace('__ID__', id),
                type: "GET",
                success: function(html) {
                    $("#criticalIssueModalContainer").html(html);
                    $("#criticalIssueEditModal").modal("show");
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'ไม่สามารถเปิดหน้าต่างแก้ไขได้'
                    });
                }
            });
        }

        function deleteCriticalIssue(id) {
            Swal.fire({
                title: 'ลบรายการนี้?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                $.ajax({
                    url: destroyUrlTemplate.replace('__ID__', id),
                    type: "POST",
                    data: {
                        _token: csrfToken,
                        _method: "DELETE"
                    },
                    success: function(res) {
                        if (res.success) {
                            reloadTable();
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบแล้ว',
                                timer: 1200,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'ไม่สามารถลบได้'
                        });
                    }
                });
            });
        }

        $(document).on('submit', '#criticalIssueAddForm', function(e) {
            e.preventDefault();
            const form = $(this);
            $.ajax({
                url: storeUrl,
                type: "POST",
                data: form.serialize(),
                success: function(res) {
                    if (res.success) {
                        $("#criticalIssueAddModal").modal("hide");
                        reloadTable();
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกแล้ว',
                            timer: 1200,
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

        $(document).on('submit', '#criticalIssueEditForm', function(e) {
            e.preventDefault();
            const form = $(this);
            const id = form.find('input[name="critical_issue_id"]').val();
            const url = updateUrlTemplate.replace('__ID__', id);
            $.ajax({
                url: url,
                type: "POST",
                data: form.serialize(),
                success: function(res) {
                    if (res.success) {
                        $("#criticalIssueEditModal").modal("hide");
                        reloadTable();
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกแล้ว',
                            timer: 1200,
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
