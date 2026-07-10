<div class="modal fade" id="criticalIssueEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="criticalIssueEditForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="critical_issue_id" value="{{ $criticalIssue->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไข Critical Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ปัญหา <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="problem" rows="4" required>{{ $criticalIssue->problem }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วิธีแก้ไข <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="solution" rows="4" required>{{ $criticalIssue->solution }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เครื่องมือที่ใช้แก้ไข <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="tools" rows="3" required>{{ $criticalIssue->tools }}</textarea>
                    </div>
                    <div class="border-top pt-3">
                        <div class="row g-2 text-muted small">
                            <div class="col-md-6">
                                <span class="fw-medium">ผู้บันทึก:</span>
                                {{ $criticalIssue->createdByUser?->full_name ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <span class="fw-medium">วันที่บันทึก:</span>
                                {{ $criticalIssue->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
