<div class="modal fade" id="issueAssignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="assignIssueForm">
                @csrf
                <input type="hidden" name="issue_id" value="{{ $issue->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">มอบหมายงานและกำหนดเวลา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div><strong>Issue:</strong> {{ $issue->issue_number }}</div>
                        <div class="text-muted">{{ $issue->title }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ผู้รับผิดชอบ</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">-- เลือกผู้รับผิดชอบ --</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}"
                                    {{ (int) $issue->assigned_to === (int) $staff->id ? 'selected' : '' }}>
                                    {{ $staff->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">วันที่เริ่มงาน</label>
                            <input type="datetime-local" class="form-control" name="planned_start_at"
                                min="{{ now()->format('Y-m-d\TH:i') }}"
                                value="{{ optional($issue->planned_start_at)->format('Y-m-d\TH:i') }}">
                            <small class="text-muted">ต้องมากกว่าหรือเท่ากับวันนี้</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">กำหนดเสร็จ</label>
                            <input type="datetime-local" class="form-control" name="due_at"
                                min="{{ now()->format('Y-m-d\TH:i') }}"
                                value="{{ optional($issue->due_at)->format('Y-m-d\TH:i') }}">
                            <small class="text-muted">ต้องมากกว่าหรือเท่ากับวันที่เริ่มงาน</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> บันทึกการมอบหมาย
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
