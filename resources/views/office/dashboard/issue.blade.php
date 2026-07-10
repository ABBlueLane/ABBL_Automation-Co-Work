@extends('layouts.office')
@section('title', 'OneClick | Issue Management Dashboard')
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0"><i class="ri-dashboard-2-line me-1"></i>
                    Issue Management Dashboard</h4>
                <div class="page-title-right d-flex flex-wrap gap-2 align-items-center">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ officeBusinessRoute('dashboard.index') }}">Dashboards</a></li>
                        <li class="breadcrumb-item active">Issue Analytics</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div id="issueDashLoading" class="text-center py-5">
        <div class="spinner-border text-primary mb-2" role="status"></div>
        <div class="text-muted">กำลังโหลดสถิติ Issue...</div>
    </div>

    <div id="issueDashContent" style="display: none;">
        <div class="row align-items-stretch mb-3">
            <div class="col-12 d-flex">
                <div class="card border-0 shadow-sm h-100 w-100 overflow-hidden" data-aos="fade-up">
                    <div class="card-header border-0 pb-0 pt-3 px-3 px-sm-4 bg-transparent">
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-start gap-3 min-w-0">
                                <span class="avatar-title rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                    <i class="ri-bar-chart-grouped-line fs-22"></i>
                                </span>
                                <div>
                                    <h5 class="card-title mb-1">สรุปตัวเลข Issue</h5>
                                    <p class="text-muted fs-13 mb-0">อัปเดตตามข้อมูลในธุรกิจที่เลือก</p>
                                </div>
                            </div>
                            <a href="{{ officeBusinessRoute('issue.index') }}" class="btn btn-soft-primary btn-sm flex-shrink-0 align-self-start">
                                <i class="ri-list-check-2 me-1"></i>
                                ไปหน้ารายการ Issue
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-3 px-3 px-sm-4 pb-4">

                        <div class="row g-3 mb-3 align-items-stretch">
                            <div class="col-12 col-md-6 d-flex">
                                <div class="rounded-3 border border-primary border-opacity-25 bg-primary bg-opacity-10 p-3 p-sm-4 w-100 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-3 min-w-0">
                                        <span class="avatar-title rounded-3 bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 52px; height: 52px;">
                                            <i class="ri-stack-line fs-24"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-primary fw-medium mb-1 fs-13">Issue ทั้งหมด</p>
                                        </div>
                                    </div>
                                    <h1 class="text-primary fw-bold mb-0" id="kpi_total">0</h1>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 d-flex">
                                <div class="d-flex flex-column gap-2 w-100">
                                    <div class="card bg-light overflow-hidden border-0 shadow-sm flex-grow-1">
                                        <div class="card-body py-3">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 fs-13">
                                                        <b class="text-secondary" id="kpi_pct_in_progress">0%</b>
                                                        <span class="text-muted fw-normal">กำลังดำเนินการ</span>
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="progress bg-secondary-subtle rounded-0" style="height: 4px;">
                                            <div class="progress-bar bg-secondary progress-bar-striped progress-bar-animated" id="kpi_progress_in_progress" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="card bg-light overflow-hidden border-0 shadow-sm flex-grow-1">
                                        <div class="card-body py-3">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 fs-13">
                                                        <b class="text-success" id="kpi_pct_completed">0%</b>
                                                        <span class="text-muted fw-normal">ปิดงานแล้ว</span>
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="progress bg-success-subtle rounded-0" style="height: 4px;">
                                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="kpi_progress_completed" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="text-muted fs-11 fw-semibold text-uppercase mb-2" style="letter-spacing: 0.04em;">สถานะและงาน</p>
                        <div class="row g-2 g-sm-3 mb-1">
                            <div class="col-6 col-lg-3">
                                <a href="{{ officeBusinessRoute('issue.index', ['status' => 'open']) }}" class="d-block h-100 text-decoration-none text-reset" style="cursor: pointer;">
                                    <div class="card card-animate border shadow-none h-100 mb-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="avatar-title rounded-2 bg-info bg-opacity-10 text-info d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                                    <i class="ri-time-line fs-16"></i>
                                                </span>
                                                <div class="min-w-0 flex-grow-1">
                                                    <p class="text-muted mb-1 fs-12 lh-sm">ยังไม่ปิดงาน <span class="text-body-secondary">(Open)</span></p>
                                                    <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-info" id="kpi_open">0</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ officeBusinessRoute('issue.index', ['assigned_to' => 'null']) }}" class="d-block h-100 text-decoration-none text-reset" style="cursor: pointer;">
                                    <div class="card card-animate border shadow-none h-100 mb-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="avatar-title rounded-2 bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                                    <i class="ri-user-unfollow-line fs-16"></i>
                                                </span>
                                                <div class="min-w-0 flex-grow-1">
                                                    <p class="text-muted mb-1 fs-12 lh-sm">ยังไม่มอบหมาย</p>
                                                    <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-warning" id="kpi_unassigned">0</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ officeBusinessRoute('issue.index', ['status' => 'done']) }}" class="d-block h-100 text-decoration-none text-reset" style="cursor: pointer;">
                                    <div class="card card-animate border shadow-none h-100 mb-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="avatar-title rounded-2 bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                                    <i class="ri-checkbox-circle-line fs-16"></i>
                                                </span>
                                                <div class="min-w-0 flex-grow-1">
                                                    <p class="text-muted mb-1 fs-12 lh-sm">ดำเนินการแล้ว</p>
                                                    <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-success" id="kpi_done">0</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ officeBusinessRoute('issue.index', ['status' => 'customer_replied']) }}" class="d-block h-100 text-decoration-none text-reset" style="cursor: pointer;">
                                    <div class="card card-animate border shadow-none h-100 mb-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="avatar-title rounded-2 bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                                    <i class="ri-chat-3-line fs-16"></i>
                                                </span>
                                                <div class="min-w-0 flex-grow-1">
                                                    <p class="text-muted mb-1 fs-12 lh-sm">ลูกค้าตอบกลับ · รอดำเนินการ</p>
                                                    <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-danger" id="kpi_customer_replied">0</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <hr class="my-3 text-muted opacity-25">

                        <p class="text-muted fs-11 fw-semibold text-uppercase mb-2" style="letter-spacing: 0.04em;">ลำดับความสำคัญ</p>
                        <div class="row g-2 g-sm-3">
                            <div class="col-4">
                                <a href="{{ officeBusinessRoute('issue.index', ['priority' => 'low']) }}" class="d-block h-100 text-decoration-none text-reset" style="cursor: pointer;">
                                    <div class="card card-animate border shadow-none h-100 mb-0 bg-success-subtle border-success">
                                        <div class="card-body p-3 text-center">
                                            <p class="text-success">ต่ำ</p>
                                            <h4 class="text-success" id="kpi_priority_low">0</h4>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="{{ officeBusinessRoute('issue.index', ['priority' => 'medium']) }}" class="d-block h-100 text-decoration-none text-reset" style="cursor: pointer;">
                                    <div class="card card-animate border shadow-none h-100 mb-0 bg-info-subtle border-info">
                                        <div class="card-body p-3 text-center">
                                            <p class="text-info">ปกติ</p>
                                            <h4 class="text-info" id="kpi_priority_medium">0</h4>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="{{ officeBusinessRoute('issue.index', ['priority' => 'high']) }}" class="d-block h-100 text-decoration-none text-reset" style="cursor: pointer;">
                                    <div class="card card-animate border shadow-none h-100 mb-0 bg-danger-subtle border-danger">
                                        <div class="card-body p-3 text-center">
                                            <p class="text-danger">เร่งด่วน</p>
                                            <h4 class="text-danger" id="kpi_priority_high">0</h4>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ปฎิทินการดำเนินการ --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">ปฎิทินการดำเนินการ</h4>
                    </div>
                    <div class="card-body">
                        <div id="issuePlanCalendar"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="issuePlanModalContainer"></div>

        <div class="row align-items-stretch mb-3">
            <div class="col-xl-4 d-flex">
                <div class="card h-100 w-100 d-flex flex-column" data-aos="fade-left">
                    <div class="card-header">
                        <h4 class="card-title mb-0">สัดส่วนตามสถานะ</h4>
                        <p class="text-muted mb-0 fs-13">จำนวนงานที่ปิดได้ (เดือนนี้) เทียบกับงานคงค้างทั้งหมด</p>
                    </div>
                    <div class="card-body d-flex align-items-center flex-grow-1">
                        <div id="chart_issue_status" class="w-100"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 d-flex">
                <div class="card h-100 w-100 d-flex flex-column" data-aos="fade-left">
                    <div class="card-header">
                        <h4 class="card-title mb-0">แนวโน้มการเปิด / ปิด Issue (12 เดือนย้อนหลัง)</h4>
                        <p class="text-muted mb-0 fs-13">พื้นที่สีน้ำเงิน = วันที่สร้าง (created_at) · เส้นสีเขียว = วันที่ปิดเคส (complete_at)</p>
                    </div>
                    <div class="card-body">
                        <div id="chart_issue_trend"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card" data-aos="fade-left">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h4 class="card-title mb-0">ผู้รับผิดชอบ — สรุปสถานะงาน</h4>
                            <p class="text-muted mb-0 fs-13">แยกตาม งานที่ยังไม่เสร็จ : รอตรวจสอบเพื่อปิดงาน : สำเร็จ</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="chart_issue_assignees"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        const issueStatusColors = ['#ffc107', '#0dcaf0', '#0d6efd', '#dc3545', '#198754'];
        const issueModalUrlTemplate = "{{ officeBusinessRoute('dashboard.issue.modal', ['id' => '__ID__']) }}";

        function issueStatCount(rows, key) {
            const row = (rows || []).find(function(x) {
                return x.key === key;
            });
            return row ? row.count : 0;
        }

        $(document).ready(function() {
            $.ajax({
                url: "{{ officeBusinessRoute('dashboard.issue.stats') }}",
                type: "GET",
                dataType: "json",
                success: function(res) {
                    $("#issueDashLoading").hide();
                    $("#issueDashContent").show();

                    const total = Number(res.total ?? 0);
                    const openCnt = Number(res.open ?? 0);
                    const doneCnt = Number(res.done ?? 0);
                    const pctInProgress = total > 0 ? Math.round((openCnt / total) * 100) : 0;
                    const pctCompleted = total > 0 ? Math.round((doneCnt / total) * 100) : 0;

                    $("#kpi_total").text(total);
                    $("#kpi_pct_in_progress").text(pctInProgress + "%");
                    $("#kpi_progress_in_progress").css("width", pctInProgress + "%").attr("aria-valuenow", pctInProgress);
                    $("#kpi_pct_completed").text(pctCompleted + "%");
                    $("#kpi_progress_completed").css("width", pctCompleted + "%").attr("aria-valuenow", pctCompleted);

                    $("#kpi_open").text(openCnt);
                    $("#kpi_unassigned").text(res.unassigned ?? 0);
                    $("#kpi_done").text(doneCnt);
                    $("#kpi_customer_replied").text(issueStatCount(res.by_status, "customer_replied"));
                    $("#kpi_priority_low").text(issueStatCount(res.by_priority, "low"));
                    $("#kpi_priority_medium").text(issueStatCount(res.by_priority, "medium"));
                    $("#kpi_priority_high").text(issueStatCount(res.by_priority, "high"));

                    // Defer ApexCharts until layout after #issueDashContent is shown (avoids wrong SVG width vs index)
                    const paintIssueCharts = function() {
                        const byStatus = res.by_status || [];
                        const statusLabels = byStatus.map(x => x.label);
                        const statusSeries = byStatus.map(x => x.count);

                        new ApexCharts(document.querySelector("#chart_issue_status"), {
                            chart: {
                                type: "donut",
                                height: 360
                            },
                            series: statusSeries,
                            labels: statusLabels,
                            colors: issueStatusColors,
                            legend: {
                                position: "bottom"
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: "65%"
                                    }
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function(val, opts) {
                                    return opts.w.config.series[opts.seriesIndex];
                                }
                            },
                            tooltip: {
                                y: {
                                    formatter: function(val) {
                                        return val + " รายการ";
                                    }
                                }
                            }
                        }).render();

                        const monthly = res.created_by_month || [];
                        const completedMonthly = res.completed_by_month || [];
                        const mLabels = monthly.map(x => x.label);
                        const mSeries = monthly.map(x => x.count);
                        const cSeries = completedMonthly.map(x => x.count);

                        new ApexCharts(document.querySelector("#chart_issue_trend"), {
                            chart: {
                                type: "area",
                                height: 350
                            },
                            series: [{
                                    name: "รายการใหม่",
                                    data: mSeries
                                },
                                {
                                    name: "ปิดเคส",
                                    data: cSeries
                                }
                            ],
                            xaxis: {
                                categories: mLabels,
                                labels: {
                                    rotate: -45
                                }
                            },
                            yaxis: {
                                min: 0,
                                labels: {
                                    formatter: function(value) {
                                        return Number(value).toLocaleString("th-TH", {
                                            maximumFractionDigits: 0
                                        });
                                    }
                                }
                            },
                            colors: ["#405189", "#198754"],
                            stroke: {
                                curve: "smooth"
                            },
                            tooltip: {
                                y: {
                                    formatter: function(value) {
                                        return Number(value).toLocaleString("th-TH", {
                                            maximumFractionDigits: 0
                                        }) + " รายการ";
                                    }
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: function(val) {
                                    return Number(val).toLocaleString("th-TH", {
                                        maximumFractionDigits: 0
                                    });
                                }
                            }
                        }).render();

                        const top = res.top_assignees || [];
                        const assigneeEl = document.querySelector("#chart_issue_assignees");
                        if (top.length === 0) {
                            assigneeEl.innerHTML =
                                '<p class="text-muted text-center py-5 mb-0">ยังไม่มี Issue ที่มอบหมายผู้รับผิดชอบในธุรกิจนี้</p>';
                        } else {
                            const assigneeNames = top.map(x => x.name);
                            const unfinishedSeries = top.map(x => Number(x.unfinished || 0));
                            const waitingReviewSeries = top.map(x => Number(x.waiting_review || 0));
                            const doneSeries = top.map(x => Number(x.done || 0));

                            new ApexCharts(assigneeEl, {
                                chart: {
                                    type: "bar",
                                    height: Math.max(320, top.length * 58),
                                    stacked: true,
                                    toolbar: {
                                        show: false
                                    }
                                },
                                series: [
                                    {
                                        name: "งานที่ยังไม่เสร็จ",
                                        data: unfinishedSeries
                                    },
                                    {
                                        name: "รอตรวจสอบเพื่อปิดงาน",
                                        data: waitingReviewSeries
                                    },
                                    {
                                        name: "สำเร็จ",
                                        data: doneSeries
                                    }
                                ],
                                plotOptions: {
                                    bar: {
                                        horizontal: true,
                                        borderRadius: 8,
                                        barHeight: "58%"
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    formatter: function(val) {
                                        return Number(val).toLocaleString("th-TH", {
                                            maximumFractionDigits: 0
                                        });
                                    }
                                },
                                xaxis: {
                                    categories: assigneeNames,
                                    labels: {
                                        formatter: function(value) {
                                            const numberValue = Number(value);
                                            if (Number.isNaN(numberValue)) {
                                                return value;
                                            }

                                            return numberValue.toLocaleString("th-TH", {
                                                maximumFractionDigits: 0
                                            });
                                        }
                                    }
                                },
                                yaxis: {
                                    labels: {
                                        show: true,
                                        maxWidth: 220,
                                        formatter: function(value) {
                                            return value;
                                        }
                                    }
                                },
                                colors: ["#f7b84b", "#405189", "#0ab39c"],
                                tooltip: {
                                    y: {
                                        formatter: function(val) {
                                            return Number(val).toLocaleString("th-TH", {
                                                maximumFractionDigits: 0
                                            }) + " รายการ";
                                        }
                                    }
                                },
                                legend: {
                                    position: "bottom",
                                    horizontalAlign: "center",
                                    markers: {
                                        radius: 12
                                    }
                                }
                            }).render();
                        }

                        const calendarEl = document.getElementById("issuePlanCalendar");
                        const events = res.calendar_events || [];
                        if (calendarEl) {
                            const calendar = new FullCalendar.Calendar(calendarEl, {
                                initialView: "dayGridMonth",
                                height: "auto",
                                locale: "th",
                                headerToolbar: {
                                    left: "prev,next today",
                                    center: "title",
                                    right: "dayGridMonth,timeGridWeek"
                                },
                                buttonText: {
                                    today: "วันนี้",
                                    month: "เดือน",
                                    week: "สัปดาห์"
                                },
                                events: events,
                                eventTimeFormat: {
                                    hour: "2-digit",
                                    minute: "2-digit",
                                    hour12: false
                                },
                                eventClick: function(info) {
                                    info.jsEvent.preventDefault();
                                    openIssuePlanModal(info.event.id);
                                }
                            });
                            calendar.render();
                        }
                    };

                    requestAnimationFrame(function() {
                        requestAnimationFrame(function() {
                            paintIssueCharts();
                            window.dispatchEvent(new Event("resize"));
                        });
                    });
                },
                error: function(xhr) {
                    $("#issueDashLoading").html(
                        '<div class="alert alert-danger mb-0">ไม่สามารถโหลดข้อมูลได้' +
                        (xhr.responseJSON && xhr.responseJSON.message ? ' — ' + xhr.responseJSON.message : '') +
                        "</div>"
                    );
                }
            });
        });

        function openIssuePlanModal(issueId) {
            $.ajax({
                url: issueModalUrlTemplate.replace('__ID__', issueId),
                type: "GET",
                success: function(html) {
                    $("#issuePlanModalContainer").html(html);
                    $("#issuePlanDetailModal").modal("show");
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'ไม่สามารถเปิดรายละเอียด Issue ได้'
                    });
                }
            });
        }
    </script>
@endsection
