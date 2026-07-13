<div class="modal fade" id="issuePlanDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดแผนงาน Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @php
                    $statusMeta = \App\Models\Issue::getStatusMeta($issue->status);

                    $statusSteps = [
                        \App\Models\Issue::STATUS_PENDING => 'รอรีวิว',
                        \App\Models\Issue::STATUS_IN_PROGRESS => 'กำลังดำเนิน...',
                        \App\Models\Issue::STATUS_WAITING_REVIEW => 'รอตรวจ',
                        \App\Models\Issue::STATUS_CUSTOMER_REPLIED => 'ลูกค้าตอบกลับ',
                        \App\Models\Issue::STATUS_DONE => 'เสร็จสิ้น',
                    ];
                    $stepKeys = array_keys($statusSteps);
                    $currentIndex = array_search($issue->status, $stepKeys);
                    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
                    $totalSteps = count($stepKeys);
                    $fillPercent = $totalSteps > 1 ? ($currentIndex / ($totalSteps - 1)) * 100 : 0;
                @endphp

                <div class="mb-3">
                    <h5 class="mb-1">{{ $issue->title }}</h5>
                    <div class="text-muted">Issue #{{ $issue->issue_number }}</div>
                </div>

                @if ($issue->status !== \App\Models\Issue::STATUS_DRAFT)
                    <div class="mb-4">
                        <label class="text-muted small d-block mb-1">สถานะ</label>
                        <div class="status-timeline-v2">
                            <div class="stv-fill" style="width: {{ $fillPercent }}%;"></div>
                            @foreach ($statusSteps as $key => $label)
                                @php
                                    $idx = $loop->index;
                                    if ($idx < $currentIndex) {
                                        $dotClass = 'stv-done';
                                        $wrapClass = '';
                                    } elseif ($idx === $currentIndex) {
                                        $dotClass = 'stv-current';
                                        $wrapClass = 'stv-active-wrap';
                                    } else {
                                        $dotClass = '';
                                        $wrapClass = '';
                                    }
                                @endphp
                                <div class="stv-dot-wrap {{ $wrapClass }}">
                                    <div class="stv-dot {{ $dotClass }}"></div>
                                    <div class="stv-label">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mb-3">
                        <span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">ผู้สร้าง</label>
                        <div>{{ $issue->creator->full_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">ผู้รับผิดชอบ</label>
                        <div>{{ $issue->assignee->full_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">เริ่มงานตามแผน</label>
                        <div>{{ $issue->planned_start_at ? $issue->planned_start_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">กำหนดเสร็จ</label>
                        <div>{{ $issue->due_at ? $issue->due_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>
                </div>

                <hr>
                <label class="text-muted small">รายละเอียดแจ้งปัญหา</label>
                <div>{!! nl2br(e($issue->firstComment->comment ?? '-')) !!}</div>
            </div>
            <div class="modal-footer">
                <a href="{{ officeBusinessRoute('issue.view', $issue->id) }}" class="btn btn-primary">
                    <i class="ri-external-link-line me-1"></i> ไปหน้า View
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.status-timeline-v2 {
    position: relative;
    display: flex;
    align-items: flex-start;
    margin-top: 16px;
    padding: 0 4px;
}
.status-timeline-v2::before {
    content: '';
    position: absolute;
    top: 8px;
    left: 8px;
    right: 8px;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}
.status-timeline-v2 .stv-fill {
    position: absolute;
    top: 8px;
    left: 8px;
    height: 2px;
    background: #f2b90b;
    z-index: 1;
    transition: width .3s ease;
}
.status-timeline-v2 .stv-dot-wrap {
    position: relative;
    z-index: 2;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.status-timeline-v2 .stv-dot-wrap:last-child {
    flex: 0;
}
.status-timeline-v2 .stv-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #e0e2e6;
    transition: all .2s ease;
}
.status-timeline-v2 .stv-dot.stv-done,
.status-timeline-v2 .stv-dot.stv-current {
    background: #f2b90b;
    border-color: #f2b90b;
}
.status-timeline-v2 .stv-dot.stv-current {
    box-shadow: 0 0 0 4px rgba(242, 185, 11, .18);
}
.status-timeline-v2 .stv-label {
    margin-top: 10px;
    font-size: .78rem;
    font-weight: 600;
    color: #9aa1ac;
    white-space: nowrap;
    text-align: center;
}
.status-timeline-v2 .stv-dot-wrap.stv-active-wrap .stv-label {
    color: #b8890a;
    font-weight: 700;
}
.status-timeline-v2 .stv-dot-wrap:first-child { align-items: flex-start; }
.status-timeline-v2 .stv-dot-wrap:first-child .stv-label { text-align: left; }
.status-timeline-v2 .stv-dot-wrap:last-child { align-items: flex-end; }
.status-timeline-v2 .stv-dot-wrap:last-child .stv-label { text-align: right; }
</style>