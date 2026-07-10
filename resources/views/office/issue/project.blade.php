@extends('layouts.office')
@section('title', 'OneClick | โปรเจค Issue')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0"><i class="ri-folder-line me-1"></i> โปรเจค Issue</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ officeBusinessRoute('issue.index') }}">Issue Management</a>
                        </li>
                        <li class="breadcrumb-item active">โปรเจค</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">รายการโปรเจค</h4>
                    <button type="button" class="btn btn-success btn-sm" onclick="openAddModal()">
                        <i class="ri-add-line me-1"></i> เพิ่มโปรเจค
                    </button>
                </div>
                <div class="card-body">
                    <div class="text-center py-5" id="showLoading">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="text-muted">กำลังโหลดข้อมูล...</div>
                    </div>

                    <div class="table-responsive" id="showTable" style="display:none;">
                        <table class="table table-hover table-striped w-100" id="issueProjectTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th width="40%">ชื่อโปรเจค</th>
                                    <th width="40%">ผู้รับผิดชอบหลัก</th>
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

    <div id="issueProjectModalContainer"></div>
@endsection

@section('script')
    <script>
        let issueProjectTable = null;
        const modalAddUrl = "{{ officeBusinessRoute('issue.project.modal.add') }}";
        const modalEditUrlTemplate = "{{ officeBusinessRoute('issue.project.modal.edit', ['id' => '__ID__']) }}";
        const storeUrl = "{{ officeBusinessRoute('issue.project.store') }}";
        const updateUrlTemplate = "{{ officeBusinessRoute('issue.project.update', ['id' => '__ID__']) }}";
        const destroyUrlTemplate = "{{ officeBusinessRoute('issue.project.destroy', ['id' => '__ID__']) }}";
        const csrfToken = "{{ csrf_token() }}";

        $(document).ready(function() {
            initTable();

            $(document).on('shown.bs.modal', '#issueProjectAddModal, #issueProjectEditModal', function() {
                const $modal = $(this);
                const $sel = $modal.find('.issue-project-responsible-select');
                if ($sel.length && !$sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2({
                        width: '100%',
                        dropdownParent: $modal,
                        placeholder: '-- เลือก --',
                    });
                }
            });

            $(document).on('hidden.bs.modal', '#issueProjectAddModal, #issueProjectEditModal', function() {
                const $sel = $(this).find('.issue-project-responsible-select');
                if ($sel.length && $sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2('destroy');
                }
            });
        });

        function initTable() {
            issueProjectTable = new DataTable('#issueProjectTable', {
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 50,
                ajax: {
                    url: "{{ officeBusinessRoute('issue.project.table') }}",
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
                    [1, 'asc']
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'responsible_name',
                        name: 'responsible_user_id',
                        orderable: true,
                        searchable: true
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
            if (issueProjectTable) {
                issueProjectTable.ajax.reload(null, false);
            }
        }

        function openAddModal() {
            $.ajax({
                url: modalAddUrl,
                type: "GET",
                success: function(html) {
                    $("#issueProjectModalContainer").html(html);
                    $("#issueProjectAddModal").modal("show");
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'ไม่สามารถเปิดหน้าต่างเพิ่มโปรเจคได้'
                    });
                }
            });
        }

        function openEditModal(id) {
            $.ajax({
                url: modalEditUrlTemplate.replace('__ID__', id),
                type: "GET",
                success: function(html) {
                    $("#issueProjectModalContainer").html(html);
                    $("#issueProjectEditModal").modal("show");
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

        function deleteProject(id) {
            Swal.fire({
                title: 'ลบโปรเจคนี้?',
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

        $(document).on('submit', '#issueProjectAddForm', function(e) {
            e.preventDefault();
            const form = $(this);
            $.ajax({
                url: storeUrl,
                type: "POST",
                data: form.serialize(),
                success: function(res) {
                    if (res.success) {
                        $("#issueProjectAddModal").modal("hide");
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

        $(document).on('submit', '#issueProjectEditForm', function(e) {
            e.preventDefault();
            const form = $(this);
            const id = form.find('input[name="issue_project_id"]').val();
            const url = updateUrlTemplate.replace('__ID__', id);
            $.ajax({
                url: url,
                type: "POST",
                data: form.serialize(),
                success: function(res) {
                    if (res.success) {
                        $("#issueProjectEditModal").modal("hide");
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
