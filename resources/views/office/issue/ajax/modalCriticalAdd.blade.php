<div class="modal fade" id="criticalIssueAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="criticalIssueAddForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่ม Critical Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ปัญหา <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="problem" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">วิธีแก้ไข <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="solution" rows="4" required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">เครื่องมือที่ใช้แก้ไข <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="tools" rows="3" required
                            placeholder="เช่น SSH, MySQL Workbench, Postman"></textarea>
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
