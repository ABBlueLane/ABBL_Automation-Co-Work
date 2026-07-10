<div class="modal fade" id="issueProjectEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="issueProjectEditForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="issue_project_id" value="{{ $project->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขโปรเจค</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อโปรเจค <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required maxlength="255"
                            value="{{ $project->name }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">ผู้รับผิดชอบหลัก <span class="text-danger">*</span></label>
                        <select class="form-select issue-project-responsible-select" name="responsible_user_id" required>
                            <option value="">-- เลือก --</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}"
                                    {{ (int) $project->responsible_user_id === (int) $staff->id ? 'selected' : '' }}>
                                    {{ $staff->full_name }}
                                </option>
                            @endforeach
                        </select>
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
