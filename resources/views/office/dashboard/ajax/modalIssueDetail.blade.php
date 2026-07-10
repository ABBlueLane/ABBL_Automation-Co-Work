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
                @endphp
                <div class="mb-3">
                    <h5 class="mb-1">{{ $issue->title }}</h5>
                    <div class="text-muted">Issue #{{ $issue->issue_number }}</div>
                </div>

                <div class="mb-3">
                    <span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                </div>

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
