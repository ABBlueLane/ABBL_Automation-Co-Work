@extends('layouts.office')

@section('title', 'Co-Work Bluelane | Uptime Monitor')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">
                    <i class="ri-pulse-line me-1"></i>
                    Uptime Monitor
                </h4>
                <div class="d-flex gap-2 align-items-center">
                    <small class="text-muted d-none d-md-inline">
                        ประวัติเริ่มนับ:
                        <span id="baselineLabel">{{ $baselineStartedAt ?: 'ยังไม่เริ่มเก็บ' }}</span>
                        · retention {{ $checksRetentionDays }} วัน
                    </small>
                    <a href="{{ route('monitor.settings') }}" class="btn btn-sm btn-soft-secondary">
                        <i class="ri-notification-3-line me-1"></i>
                        ตั้งค่า LINE
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" id="btnRunChecks">
                        <i class="ri-refresh-line me-1"></i>
                        Run Check Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-2">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">สถานะปัจจุบัน</p>
                    <h3 class="mb-0" id="kpiOverall">—</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">ล่มในวันนี้</p>
                    <h3 class="mb-0" id="kpiToday">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">ล่มใน 7 วัน</p>
                    <h3 class="mb-0" id="kpi7d">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">ล่มในเดือนนี้</p>
                    <h3 class="mb-0" id="kpiMonth">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Downtime 7 วัน</p>
                    <h3 class="mb-0" id="kpiDowntime7d">0 นาที</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Uptime 7 วัน</p>
                    <h3 class="mb-0" id="kpiUptime">—</h3>
                    <small class="text-muted" id="kpiLatency">latency —</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">สถานะปัจจุบัน</h5>
                    <small class="text-muted" id="targetCountBadge">0 Endpoints</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Target / Endpoint</th>
                                    <th>สถานะ</th>
                                    <th>HTTP</th>
                                    <th>Latency</th>
                                    <th>เช็กล่าสุด</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="statusRows"></tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center py-2">
                    <small class="text-muted" id="statusFooterText">แสดงทั้งหมด 0 รายการ</small>
                    <small class="text-muted" id="statusFooterHealth">กำลังรอข้อมูล</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">ช่วงเวลาที่ล่มบ่อย (รายชั่วโมง)</h5>
                    <small class="text-muted" id="hourlyStatusLabel">Zero Failures</small>
                </div>
                <div class="card-body">
                    <div id="hourlyChart"></div>
                    <div class="alert alert-light border mt-3 mb-0">
                        <div class="fw-semibold" id="hourlyInsightTitle">ไม่มีประวัติระบบล่มในช่วง 24 ชั่วโมงที่ผ่านมา</div>
                        <div class="text-muted small" id="hourlyInsightDesc">บริการทั้งหมดตอบสนองตามเกณฑ์มาตรฐาน SLA</div>
                        <div class="text-muted small mt-1" id="hourlyTopHours"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">Incident ล่าสุด</h5>
            <small class="text-muted">Timezone: {{ $displayTimezone }}</small>
        </div>
        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">จากวันที่</label>
                    <input type="date" class="form-control form-control-sm" id="filterFrom">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">ถึงวันที่</label>
                    <input type="date" class="form-control form-control-sm" id="filterTo">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Target</label>
                    <select class="form-select form-select-sm" id="filterTarget">
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">สถานะ</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">ทั้งหมด</option>
                        <option value="open">open</option>
                        <option value="resolved">resolved</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-soft-primary w-100" id="btnApplyIncidentFilter">กรอง</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>เริ่ม</th>
                            <th>จบ</th>
                            <th>ระยะเวลา</th>
                            <th>Target</th>
                            <th>HTTP / Error</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody id="incidentRows"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTargetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไข Monitor Target</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTargetForm">
                    <div class="modal-body">
                        <input type="hidden" id="targetId">
                        <div class="mb-3">
                            <label class="form-label">Target Name</label>
                            <input type="text" class="form-control" id="targetName" readonly>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">URL</label>
                                <input type="url" class="form-control" id="targetUrl" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Method</label>
                                <select class="form-select" id="targetMethod">
                                    <option value="GET">GET</option>
                                    <option value="HEAD">HEAD</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Interval (seconds)</label>
                                <input type="number" min="10" max="3600" class="form-control" id="targetInterval" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Timeout (seconds)</label>
                                <input type="number" min="1" max="120" class="form-control" id="targetTimeout" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expected Status</label>
                                <input type="text" class="form-control" id="targetExpectedStatus" placeholder="200,204">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Failure Threshold</label>
                                <input type="number" min="1" max="10" class="form-control" id="targetFailureThreshold" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Success Threshold</label>
                                <input type="number" min="1" max="10" class="form-control" id="targetSuccessThreshold" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="targetIsActive">
                                    <label class="form-check-label" for="targetIsActive">เปิดใช้งาน target นี้</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveTarget">บันทึกและ Run Check</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const monitorRoutes = {
            summary: "{{ route('monitor.stats.summary') }}",
            status: "{{ route('monitor.status') }}",
            incidents: "{{ route('monitor.incidents') }}",
            byHour: "{{ route('monitor.stats.by-hour') }}",
            runChecks: "{{ route('monitor.run-checks') }}",
            updateTargetTemplate: "{{ route('monitor.targets.update', ['target' => '__TARGET_ID__']) }}",
        };

        let hourlyChart;
        let targetMap = {};
        let editTargetModal;

        function statusBadge(status) {
            if (status === 'up') return '<span class="badge bg-success">UP</span>';
            if (status === 'down') return '<span class="badge bg-danger">DOWN</span>';
            if (status === 'degraded') return '<span class="badge bg-warning text-dark">DEGRADED</span>';
            if (status === 'inactive') return '<span class="badge bg-secondary">INACTIVE</span>';
            return '<span class="badge bg-secondary">UNKNOWN</span>';
        }

        function formatDuration(seconds) {
            const sec = Number(seconds || 0);
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            if (h > 0) return `${h} ชม. ${m} นาที`;
            return `${m} นาที`;
        }

        function formatDurationCompact(seconds) {
            const sec = Number(seconds || 0);
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            if (h > 0) return `${h}h ${m}m`;
            if (m > 0) return `${m}m ${s}s`;
            return `${s}s`;
        }

        function overallStatusLabel(targets) {
            const active = (targets || []).filter(t => t.is_active);
            if (active.length === 0) return 'UNKNOWN';
            if (active.some(t => t.status === 'down')) return 'DOWN';
            if (active.some(t => t.status === 'degraded')) return 'DEGRADED';
            if (active.every(t => t.status === 'up')) return 'UP';
            return 'UNKNOWN';
        }

        async function loadSummary() {
            const res = await $.getJSON(monitorRoutes.summary, { range: '7d' });
            $('#kpiToday').text(res.totals.incidents_today ?? 0);
            $('#kpi7d').text(res.totals.incidents_7d ?? 0);
            $('#kpiMonth').text(res.totals.incidents_month ?? 0);
            $('#kpiDowntime7d').text(formatDuration(res.totals.downtime_seconds_7d ?? 0));
            $('#kpiUptime').text((res.uptime_percent ?? 0) + '%');
            const p50 = res.latency?.p50;
            const p95 = res.latency?.p95;
            $('#kpiLatency').text(
                p50 != null || p95 != null
                    ? `p50 ${p50 ?? '—'} / p95 ${p95 ?? '—'} ms`
                    : 'latency —'
            );
        }

        async function loadStatus() {
            const res = await $.getJSON(monitorRoutes.status);
            targetMap = {};
            let activeCount = 0;
            let upCount = 0;

            if (res.baseline_started_at) {
                const baselineLocal = (res.baseline_started_at || '').replace('T', ' ').slice(0, 16);
                if (baselineLocal) {
                    $('#baselineLabel').text(baselineLocal);
                }
            }

            const $filterTarget = $('#filterTarget');
            const previousTarget = $filterTarget.val();
            $filterTarget.find('option:not([value=""])').remove();

            const rows = (res.targets || []).map(function(item) {
                targetMap[item.id] = item;
                $filterTarget.append(`<option value="${item.id}">${item.name}</option>`);
                const expectedStatusText = (item.expected_status || []).join(', ');
                if (item.is_active) activeCount++;
                if (item.status === 'up') upCount++;

                return `
                    <tr>
                        <td>
                            <div class="fw-semibold">${item.name}</div>
                            <div class="small text-break">${item.url}</div>
                            <div class="small text-muted">
                                ${item.method || 'GET'} • every ${item.interval_seconds ?? 60}s • timeout ${item.timeout_seconds ?? 10}s
                            </div>
                            <div class="small text-muted">fail x${item.failure_threshold ?? 2}, recover x${item.success_threshold ?? 1}, expect [${expectedStatusText || '200'}]</div>
                        </td>
                        <td>${statusBadge(item.status)}</td>
                        <td>${item.http_status ?? '-'}</td>
                        <td>${item.latency_ms ? item.latency_ms + ' ms' : '-'}</td>
                        <td>${item.checked_at ?? '-'}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="openEditTargetModal(${item.id})" aria-label="Settings for ${item.name}">
                                <i class="ri-settings-3-line"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            $filterTarget.val(previousTarget || '');
            $('#kpiOverall').text(overallStatusLabel(res.targets || []));
            $('#statusRows').html(rows || '<tr><td colspan="6" class="text-center text-muted">ยังไม่มีข้อมูล</td></tr>');
            $('#targetCountBadge').text(`${activeCount} Endpoints`);
            $('#statusFooterText').text(`แสดงทั้งหมด ${activeCount} รายการที่กำลังทำงาน`);
            $('#statusFooterHealth').text(activeCount > 0 && upCount === activeCount ? 'สถานะปกติทุกโหนด' : 'มีโหนดต้องเฝ้าระวัง');
        }

        function openEditTargetModal(targetId) {
            const item = targetMap[targetId];
            if (!item) {
                Swal.fire('ไม่พบข้อมูล', 'ไม่พบ target ที่ต้องการแก้ไข', 'warning');
                return;
            }

            $('#targetId').val(item.id);
            $('#targetName').val(item.name);
            $('#targetUrl').val(item.url || '');
            $('#targetMethod').val(item.method || 'GET');
            $('#targetInterval').val(item.interval_seconds ?? 60);
            $('#targetTimeout').val(item.timeout_seconds ?? 10);
            $('#targetExpectedStatus').val((item.expected_status || [200]).join(','));
            $('#targetFailureThreshold').val(item.failure_threshold ?? 2);
            $('#targetSuccessThreshold').val(item.success_threshold ?? 1);
            $('#targetIsActive').prop('checked', !!item.is_active);
            editTargetModal.show();
        }

        async function loadIncidents() {
            const params = {};
            const from = $('#filterFrom').val();
            const to = $('#filterTo').val();
            const targetId = $('#filterTarget').val();
            const status = $('#filterStatus').val();
            if (from) params.from = from;
            if (to) params.to = to;
            if (targetId) params.target_id = targetId;
            if (status) params.status = status;

            const res = await $.getJSON(monitorRoutes.incidents, params);
            const rows = (res.items || []).map(function(item) {
                const errorText = item.trigger_http_status ? `HTTP ${item.trigger_http_status}` : (item.trigger_error || '-');
                return `
                    <tr>
                        <td>${item.started_at ?? '-'}</td>
                        <td>${item.ended_at ?? 'ยังล่มอยู่'}</td>
                        <td>${formatDurationCompact(item.duration_seconds)}</td>
                        <td>${item.target ?? '-'}</td>
                        <td>${errorText}</td>
                        <td>${item.status === 'open' ? '<span class="badge bg-danger">open</span>' : '<span class="badge bg-success">resolved</span>'}</td>
                    </tr>
                `;
            }).join('');

            $('#incidentRows').html(rows || '<tr><td colspan="6" class="text-center text-muted">ยังไม่มี incident</td></tr>');
        }

        async function loadByHour() {
            const res = await $.getJSON(monitorRoutes.byHour, { range: '30d' });
            const categories = (res.hours || []).map(x => String(x.hour).padStart(2, '0') + ':00');
            const values = (res.hours || []).map(x => x.count);
            const totalFailures = values.reduce((sum, val) => sum + Number(val || 0), 0);
            const top = (res.top_hours || []).filter(x => Number(x.count) > 0).slice(0, 5);
            $('#hourlyTopHours').text(
                top.length
                    ? 'Top hours: ' + top.map(x => String(x.hour).padStart(2, '0') + ':00 (' + x.count + ')').join(', ')
                    : ''
            );

            $('#hourlyStatusLabel').text(totalFailures > 0 ? `${totalFailures} Failures` : 'Zero Failures');
            $('#hourlyInsightTitle').text(totalFailures > 0
                ? 'พบประวัติระบบล่มในช่วง 30 วันที่ผ่านมา'
                : 'ไม่มีประวัติระบบล่มในช่วง 30 วันที่ผ่านมา');
            $('#hourlyInsightDesc').text(totalFailures > 0
                ? 'ควรตรวจสอบช่วงเวลาที่เกิดซ้ำเพื่อวางแผนป้องกัน (โดยเฉพาะกลางคืน)'
                : 'บริการทั้งหมดตอบสนองตามเกณฑ์มาตรฐาน SLA');

            if (hourlyChart) {
                hourlyChart.updateOptions({ xaxis: { categories } });
                hourlyChart.updateSeries([{ name: 'Incidents', data: values }]);
                return;
            }

            hourlyChart = new ApexCharts(document.querySelector('#hourlyChart'), {
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                series: [{ name: 'Incidents', data: values }],
                xaxis: { categories },
                yaxis: { min: 0, forceNiceScale: true },
                colors: ['#f06548'],
                dataLabels: { enabled: true },
                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            });
            hourlyChart.render();
        }

        async function reloadAll() {
            await Promise.all([loadSummary(), loadStatus(), loadIncidents(), loadByHour()]);
        }

        $(document).ready(function() {
            editTargetModal = new bootstrap.Modal(document.getElementById('editTargetModal'));

            reloadAll().catch(function() {
                Swal.fire('ผิดพลาด', 'โหลดข้อมูล monitor ไม่สำเร็จ', 'error');
            });

            setInterval(function() {
                reloadAll().catch(function() {});
            }, 60000);

            $('#btnApplyIncidentFilter').on('click', function() {
                loadIncidents().catch(function() {
                    Swal.fire('ผิดพลาด', 'กรอง incident ไม่สำเร็จ', 'error');
                });
            });

            $('#btnRunChecks').on('click', async function() {
                const $btn = $(this);
                $btn.prop('disabled', true);
                try {
                    await $.ajax({
                        url: monitorRoutes.runChecks,
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    });
                    await reloadAll();
                    Swal.fire({ icon: 'success', title: 'Run check สำเร็จ', timer: 1000, showConfirmButton: false });
                } catch (e) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถรัน check ได้', 'error');
                } finally {
                    $btn.prop('disabled', false);
                }
            });

            $('#editTargetForm').on('submit', async function(e) {
                e.preventDefault();

                const targetId = $('#targetId').val();
                const expectedStatus = ($('#targetExpectedStatus').val() || '')
                    .split(',')
                    .map(function(x) { return Number(String(x).trim()); })
                    .filter(function(x) { return Number.isInteger(x) && x >= 100 && x <= 599; });

                if (expectedStatus.length === 0) {
                    Swal.fire('ข้อมูลไม่ถูกต้อง', 'Expected Status ต้องมีอย่างน้อย 1 ค่า (เช่น 200)', 'warning');
                    return;
                }

                const payload = {
                    url: $('#targetUrl').val(),
                    method: $('#targetMethod').val(),
                    interval_seconds: Number($('#targetInterval').val()),
                    timeout_seconds: Number($('#targetTimeout').val()),
                    failure_threshold: Number($('#targetFailureThreshold').val()),
                    success_threshold: Number($('#targetSuccessThreshold').val()),
                    expected_status: expectedStatus,
                    is_active: $('#targetIsActive').is(':checked') ? 1 : 0,
                };

                const $saveBtn = $('#btnSaveTarget');
                $saveBtn.prop('disabled', true);

                try {
                    await $.ajax({
                        url: monitorRoutes.updateTargetTemplate.replace('__TARGET_ID__', targetId),
                        method: 'PUT',
                        data: payload,
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    });
                    editTargetModal.hide();
                    await reloadAll();
                    Swal.fire({ icon: 'success', title: 'บันทึกแล้วและรันเช็คใหม่แล้ว', timer: 1300, showConfirmButton: false });
                } catch (xhr) {
                    const message = xhr?.responseJSON?.message || 'ไม่สามารถบันทึก target ได้';
                    Swal.fire('ผิดพลาด', message, 'error');
                } finally {
                    $saveBtn.prop('disabled', false);
                }
            });
        });
    </script>
@endsection
