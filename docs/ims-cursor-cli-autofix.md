# IMS → Cursor CLI Autofix

ระบบนี้เชื่อม IMS กับ **Cursor Agent CLI** ได้ทั้งสองแบบ
- wrapper: `agent -p ...` (บาง server)
- binary: `cursor agent -p ...` (บาง server ที่มีแค่ `cursor` binary)
เพื่อเริ่มแก้ปัญหาแบบอัตโนมัติเมื่อมี issue ใหม่สถานะ `pending`

## หน้า UI ในระบบ

เมนู sidebar: **Cursor Autofix** (เมนูหลัก ถัดจาก Issue Management)

| หน้า | URL | การใช้งาน |
| --- | --- | --- |
| รายการงาน | `/cursor-autofix` | ดูสถานะรันทั้งหมด กรองตามสถานะ |
| สั่งรัน | `/cursor-autofix/create` | ใส่ Issue ID + เลือก repo (optional) |
| รายละเอียด | `/cursor-autofix/{id}` | ดู prompt/command/stdout/stderr และลิงก์ไปหน้า IMS |
| ทดสอบ CLI / แชท | `/cursor-autofix/chat` | ปุ่มทดสอบการเชื่อมต่อ + แชทกับ Cursor Agent |

ในหน้ารายการมีปุ่ม **ทดสอบ CLI / แชท** และในหน้าแชทมีปุ่ม **ทดสอบการเชื่อมต่อ CLI**
- โหมดแนะนำตอนทดสอบ: `ask` (อ่านอย่างเดียว)
- เลือก repo workspace ได้ (เช่น AB_Gateway) หรือใช้ directory ของระบบ IMS

## พฤติกรรม

1. มี IMS issue ถูกสร้าง/ส่งเป็น `pending` (จากเว็บหรือ LINE → IMS)
2. `IssueObserver` เรียก `ImsCursorAutofixService`
3. แมป `issue.url` → repo จาก `config/cursor.php`
   - ตัวอย่าง: `https://gateway.co.th/...` → **AB_Gateway**
4. สร้างแถวใน `cursor_autofix_runs` แล้วคิว `ProcessImsCursorAutofix`
5. Job จะ:
   - สร้าง/สลับ git branch `cursor/ims-{issue_number}-autofix` (ถ้าเปิด)
 - รัน `agent -p --force` หรือ `cursor agent -p --force` ใน directory ของ repo (ขึ้นกับ `CURSOR_CLI_BINARY`)
   - บันทึก stdout/stderr
   - โพสต์คอมเมนต์สรุปกลับใน issue (ถ้าเปิด)

## การแมป repo

แก้ที่ `config/cursor.php` → `repos`:

| key | hosts / url_contains | path (ภายใต้ `CURSOR_REPOS_PATH`) |
| --- | -------------------- | -------------------------------- |
| `AB_Gateway` | `gateway.co.th`, `www.gateway.co.th` | `AB_Gateway` |

เพิ่ม mapping ได้โดยใส่ entry ใหม่ใน `repos`

บังคับเลือก repo ด้วย artisan:

```bash
php artisan ims:cursor-autofix {issue_id} --repo=AB_Gateway --sync
```

## Environment

ดู `.env.example` คีย์ที่ขึ้นต้นด้วย `CURSOR_`

ค่าแนะนำตอนเริ่มใช้งาน:

```env
CURSOR_AUTOFIX_ENABLED=true
CURSOR_AUTOFIX_DRY_RUN=true
CURSOR_API_KEY=...
CURSOR_CLI_BINARY=agent  # หรือใช้ path ของ `cursor` binary หาก server ไม่มี `agent`
CURSOR_REPOS_PATH=/var/www/repos
CURSOR_REPO_AB_GATEWAY_PATH=AB_Gateway
CURSOR_AUTOFIX_SYSTEM_USER_ID=1
```

- `DRY_RUN=true` — ไม่เรียก CLI จริง แค่บันทึกว่าจะรันอะไร (ปลอดภัยสำหรับทดสอบ)
- `DRY_RUN=false` — รันคำสั่งจริง ต้องมี binary ที่กำหนดใน `CURSOR_CLI_BINARY` + API key + checkout ของ repo บนเครื่อง

## ข้อกำหนดบน server

1. ติดตั้ง Cursor CLI: `curl https://cursor.com/install -fsS | bash`
2. ตั้ง `CURSOR_API_KEY`
3. Checkout repo เป้าหมายไว้ใต้ `CURSOR_REPOS_PATH` (เช่น `/var/www/repos/AB_Gateway`)
4. รัน queue worker: `php artisan queue:work`
5. เปิด `CURSOR_AUTOFIX_ENABLED=true`

## คำสั่งที่เกี่ยวข้อง

```bash
# คิวอัตโนมัติตาม URL mapping
php artisan ims:cursor-autofix 123

# รันทันที + เลือก repo เอง
php artisan ims:cursor-autofix 123 --repo=AB_Gateway --sync
```

## ตาราง audit

`cursor_autofix_runs` เก็บสถานะ `queued|running|succeeded|failed|skipped`, repo ที่เลือก, branch, prompt, command, output

## ขอบเขตปัจจุบัน

- โฟกัส backend automation (ยังไม่มีหน้า UI เลือก repo ในฟอร์ม IMS)
- ไม่ push remote โดยอัตโนมัติ — agent ถูกสั่งให้แก้ในเครื่อง / commit เฉพาะเมื่อเหมาะสม
- ถ้า URL ไม่ตรง mapping → สถานะ `skipped`
