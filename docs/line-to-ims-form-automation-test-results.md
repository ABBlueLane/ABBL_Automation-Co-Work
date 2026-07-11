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

## Phase 2 — Core Services (2026-07-10)

### Services

- `app/Services/Line/Ims/IssueSubmissionService.php` — สร้าง/อัปเดต draft + submit (mirror `IssueController` logic)
- `app/Services/Line/Ims/IssueCreateFormMapper.php` — แมปข้อความ LINE → form fields (heuristic + structured `@OA เรื่อง:` labels)
- `app/Services/Line/Ims/IssueCreateFormCompleter.php` — ตรวจ `missing_fields` / `isComplete`
- `app/Services/Line/Ims/LineContentDownloader.php` — ดาวน์โหลดไฟล์จาก LINE Content API → `storage/app/public/issue/{business}/`
- `app/Services/Line/Ims/LineImsFormProcessor.php` — orchestrator หลัก (sync draft, auto-submit, audit log, reply/push)
- `LineMessagingClient::pushText()` — สำหรับแจ้งผลหลัง submit

### Automated Tests

- `php artisan test tests/Unit/Services/Line/Ims/` — passed, 14 tests, 29 assertions
- `php artisan test` — passed, 41 tests, 100 assertions

### Test Coverage (Phase 2)

| Test | ผล |
| ---- | -- |
| `IssueCreateFormMapper` — title/comment/url/priority/structured labels | ✅ |
| `IssueCreateFormCompleter` — missing fields / isComplete | ✅ |
| `IssueSubmissionService` — create draft + update + submit | ✅ |

## Phase 3 — LINE Webhook Integration (2026-07-10)

### Changes

- `ProcessLineWebhookEvent` — START เรียก `initializeForm()`, STOP แจ้ง draft ค้าง, หลังบันทึก message เรียก `LineImsFormProcessor`
- `LineCommandParser` — เพิ่ม `SUBMIT` / `RESET` commands (ต้อง mention OA)
- Redelivery guard — ข้าม IMS processing ถ้า `LineChatMessage` มีอยู่แล้ว + ข้าม submit ซ้ำผ่าน `line_ims_submissions.webhook_event_id`

### Automated Tests

- `php artisan test tests/Feature/LineImsWebhookTest.php` — passed, 6 tests
- `php artisan test` — passed, 49 tests, 137 assertions

### Test Coverage (Phase 3)

| Test | ผล |
| ---- | -- |
| START สร้าง form_state + draft issue | ✅ |
| ข้อความอัปเดต title ใน form_state | ✅ |
| auto-submit เมื่อครบ title + url | ✅ |
| `ไม่มี url` intent → submit สำเร็จ | ✅ |
| redelivery ไม่ process form ซ้ำ | ✅ |
| image message → files ใน form_state | ✅ |
| STOP เก็บ draft ค้างไว้ | ✅ |
