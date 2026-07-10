# LINE → IMS Form Automation Test Results

ไฟล์นี้แยกผลทดสอบออกจาก `docs/line-to-ims-form-automation.md` เพื่อลดขนาดเอกสาร implementation หลัก

## Phase 1 — Database & Model (2026-07-10)

### Migration

- `php artisan migrate` — migrations `2026_07_10_180000_add_ims_form_columns_to_line_chat_sources_table` และ `2026_07_10_180100_create_line_ims_submissions_table` applied สำเร็จ
- ตาราง `line_chat_sources` มี column ใหม่: `business_id`, `form_type`, `draft_issue_id`, `form_state`
- ตาราง `line_ims_submissions` สร้างสำเร็จพร้อม FK ไปยัง `line_chat_sources` และ `issues`

### Model

- `LineChatSource` — cast `form_state` เป็น array, relationships `draftIssue()`, `business()`, `imsSubmissions()`, helper `defaultIssueCreateFormState()`
- `LineImsSubmission` — model สำหรับ audit log พร้อม cast `form_state` และ `submitted_at`

### Automated Tests

- `php artisan test tests/Unit/LineChatSourceImsFormTest.php` — passed, 7 tests, 13 assertions
- `php artisan test` — passed, 27 tests, 71 assertions

### Test Coverage (Phase 1)

| Test | ผล |
| ---- | -- |
| `line_chat_sources` มี IMS form columns | ✅ |
| `form_state` cast เป็น array | ✅ |
| `draftIssue()` relationship | ✅ |
| `business()` relationship | ✅ |
| `defaultIssueCreateFormState()` schema | ✅ |
| `line_ims_submissions` table exists | ✅ |
| audit log บันทึกผ่าน `imsSubmissions()` | ✅ |
