@extends('layouts.public')
@section('title', 'OneClick | ผู้ใช้งานระบบ')

@section('content')
    <div class="container py-4">
        <div class="row">
            {{-- LEFT --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <a href="{{ route('business.select') }}" class="btn btn-outline-dark btn-sm flex-shrink-0">
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
                                    <li class="breadcrumb-item active">
                                        Issue Management
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card shadow-sm" data-aos="fade-left">
                    {{-- Header --}}
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">
                            รายการ Issue {{ $business->business_name }}
                        </h4>
                        <div class="flex-shrink-0">
                            <a href="{{ route('issue.create', $business) }}" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i>
                                เพิ่ม Issue
                            </a>
                        </div>
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
                                    <input type="text" class="form-control" id="qt_wording"
                                        placeholder="พิมพ์คำค้นหา...">
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label">
                                        <i class="ri-filter-3-line me-1"></i>
                                        สถานะ
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
                        <div id="showTable" style="display:none;">
                            <table class="table table-hover table-striped w-100" id="issueTable">
                                <thead>
                                    <tr>
                                        <th width="8%">#</th>
                                        <th width="15%">No.</th>
                                        <th width="37%">เรื่อง</th>
                                        <th width="20%">ผู้รับผิดชอบ</th>
                                        <th width="20%">สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        let issueTable = null;

        $(document).ready(function() {
            $(".select").select2({
                width: "100%",
                minimumResultsForSearch: -1
            });
            initTable();
            $("#qt_wording").keypress(function(e) {
                if (e.which == 13) {
                    reloadTable();
                }
            });
            $("#qt_status").on('change', reloadTable);
        });

        function initTable() {
            issueTable = new DataTable('#issueTable', {
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 100,
                ajax: {
                    url: "{{ route('issue.table', $business) }}",
                    type: "GET",
                    data: function(d) {
                        d.wording = $("#qt_wording").val();
                        d.status = $("#qt_status").val();
                    },
                    error: function() {
                        $("#showLoading").hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถโหลดข้อมูลได้'
                        });
                    }
                },
                order: [[1, 'desc']],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'issue_number', name: 'issue_number' },
                    { data: 'title_html', name: 'title' },
                    { data: 'assigned_to_html', name: 'assigned_to', orderable: false, searchable: false },
                    { data: 'status_html', name: 'status' }
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
            if (issueTable) {
                issueTable.ajax.reload();
            }
        }

        function resetFilter() {
            $("#qt_wording").val('');
            $("#qt_status").val('').trigger('change');
            reloadTable();
        }
    </script>
@endsection
