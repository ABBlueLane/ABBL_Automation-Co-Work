# LINE Group Chat Webhook Implementation

เอกสารนี้เป็นสเปกสำหรับ implement ระบบดึงข้อมูลแชทจากกลุ่ม LINE ผ่าน LINE Official Account (OA) ที่ถูกเพิ่มเข้าไปในกลุ่ม โดยเริ่มเก็บข้อมูลเมื่อผู้ใช้แท็ก OA พร้อมคำสั่งเริ่มต้น

## เป้าหมาย

- รับ webhook จาก LINE Messaging API
- ตรวจสอบลายเซ็น `x-line-signature` ก่อนประมวลผลทุกครั้ง
- รองรับ webhook จาก group chat และ multi-person chat
- เริ่มเก็บข้อความในกลุ่มเมื่อ user แท็ก OA ด้วยคำสั่ง เช่น `@ชื่อOA เริ่มเก็บข้อมูล`
- หยุดเก็บข้อความเมื่อ user แท็ก OA ด้วยคำสั่ง เช่น `@ชื่อOA หยุดเก็บข้อมูล`
- เก็บข้อความหลังจากสถานะกลุ่มเป็น active เท่านั้น
- แยกข้อมูลตาม `groupId` หรือ `roomId`

## ข้อกำหนดจาก LINE

- Webhook URL ต้องเป็น HTTPS และตั้งค่าใน LINE Developers Console
- LINE อาจส่ง webhook ที่ `events` เป็น array ว่างเพื่อทดสอบ endpoint ระบบต้องตอบ `200`
- ห้าม parse, format, หรือแก้ไข request body ก่อน verify signature
- Signature ใช้ HMAC-SHA256 ด้วย Channel Secret แล้ว encode เป็น Base64
- Header field name ควรอ่านแบบ case-insensitive เช่น `X-Line-Signature` หรือ `x-line-signature`
- ใน group chat จะใช้ `source.type = group` และ `source.groupId`
- ใน multi-person chat จะใช้ `source.type = room` และ `source.roomId`
- ข้อความที่มีการ mention OA จะมี `message.mention.mentionees` และรายการที่แท็ก OA ตัวเองจะมี `isSelf = true`

อ้างอิงหลัก:

- Receive messages: https://developers.line.biz/en/docs/messaging-api/receiving-messages/
- Verify webhook signature: https://developers.line.biz/en/docs/messaging-api/verify-webhook-signature/
- Messaging API reference: https://developers.line.biz/en/reference/messaging-api/

## Environment Variables

เพิ่มค่าใน `.env`

```env
LINE_CHANNEL_SECRET=
LINE_CHANNEL_ACCESS_TOKEN=
LINE_WEBHOOK_ROUTE_SECRET=
```

คำอธิบาย:

- `LINE_CHANNEL_SECRET`: ใช้ verify webhook signature
- `LINE_CHANNEL_ACCESS_TOKEN`: ใช้เรียก LINE API เช่น reply message หรือดึง group/member profile
- `LINE_WEBHOOK_ROUTE_SECRET`: optional path secret เพื่อทำ URL ให้เดายาก เช่น `/line/webhook/{secret}` แต่ยังต้อง verify signature เสมอ

เพิ่มใน `config/services.php`

```php
'line' => [
    'channel_secret' => env('LINE_CHANNEL_SECRET'),
    'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
    'webhook_route_secret' => env('LINE_WEBHOOK_ROUTE_SECRET'),
],
```

## Proposed Routes

ใช้ route แยกสำหรับ webhook

```php
use App\Http\Controllers\LineWebhookController;

Route::post('/line/webhook/{secret?}', LineWebhookController::class)
    ->name('line.webhook');
```

ถ้าใช้ web middleware ต้องยกเว้น CSRF สำหรับ endpoint นี้ใน `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'line/webhook',
        'line/webhook/*',
    ]);
})
```

## Data Model

### `line_chat_sources`

เก็บสถานะของ group/room

| column               | type               | note                        |
| -------------------- | ------------------ | --------------------------- |
| `id`                 | bigint             | primary key                 |
| `source_type`        | string             | `group` หรือ `room`         |
| `source_id`          | string             | `groupId` หรือ `roomId`     |
| `display_name`       | string nullable    | ชื่อกลุ่ม ถ้าดึงได้         |
| `is_collecting`      | boolean            | สถานะเปิด/ปิดการเก็บข้อความ |
| `started_by_user_id` | string nullable    | user ที่สั่งเริ่ม           |
| `started_at`         | timestamp nullable | เวลาเริ่ม                   |
| `stopped_by_user_id` | string nullable    | user ที่สั่งหยุด            |
| `stopped_at`         | timestamp nullable | เวลาหยุด                    |
| `created_at`         | timestamp          |                             |
| `updated_at`         | timestamp          |                             |

Unique index:

```text
unique(source_type, source_id)
```

### `line_chat_messages`

เก็บข้อความที่รับจาก group/room ตอน active

| column                | type               | note                                |
| --------------------- | ------------------ | ----------------------------------- |
| `id`                  | bigint             | primary key                         |
| `line_chat_source_id` | foreignId          | references `line_chat_sources.id`   |
| `webhook_event_id`    | string nullable    | ใช้กัน duplicate ถ้า LINE redeliver |
| `reply_token`         | string nullable    | token ใช้ตอบกลับ เฉพาะช่วงสั้น ๆ    |
| `message_id`          | string nullable    | LINE message ID                     |
| `message_type`        | string             | เช่น `text`, `image`, `sticker`     |
| `text`                | text nullable      | เฉพาะ text message                  |
| `sender_user_id`      | string nullable    | `source.userId` ถ้ามี               |
| `sent_at`             | timestamp nullable | จาก `timestamp` ของ event           |
| `raw_event`           | json               | event เต็มสำหรับ debug/audit        |
| `created_at`          | timestamp          |                                     |
| `updated_at`          | timestamp          |                                     |

Index ที่ควรมี:

```text
index(line_chat_source_id, sent_at)
unique(webhook_event_id)
index(message_id)
index(sender_user_id)
```

### `line_webhook_logs`

optional แต่แนะนำสำหรับ debug ช่วงแรก

| column            | type             | note                                           |
| ----------------- | ---------------- | ---------------------------------------------- |
| `id`              | bigint           | primary key                                    |
| `signature_valid` | boolean          | ผล verify                                      |
| `destination`     | string nullable  | bot user ID                                    |
| `event_count`     | unsigned integer | จำนวน events                                   |
| `raw_body_hash`   | string           | SHA-256 ของ raw body ไม่ต้องเก็บ raw body เสมอ |
| `error_message`   | text nullable    | กรณี error                                     |
| `created_at`      | timestamp        |                                                |

## Processing Flow

1. LINE ส่ง `POST /line/webhook/{secret?}`
2. Controller อ่าน raw body ด้วย `$request->getContent()`
3. Controller อ่าน signature จาก header `x-line-signature`
4. Verify signature:

```php
$hash = hash_hmac('sha256', $rawBody, $channelSecret, true);
$expectedSignature = base64_encode($hash);
$valid = hash_equals($expectedSignature, $receivedSignature);
```

5. ถ้า signature ไม่ถูกต้อง ให้ตอบ `403` และไม่ process event
6. ถ้า valid แล้วค่อย `json_decode($rawBody, true)`
7. ถ้า `events` ว่าง ให้ตอบ `200`
8. ส่ง events เข้า queue job เช่น `ProcessLineWebhookEvent`
9. Controller ตอบ `200` ให้ LINE เร็วที่สุด
10. Job process ทีละ event และบันทึกข้อมูลลง database

## Command Detection

ระบบจะเปิด/ปิดเก็บข้อมูลเฉพาะเมื่อครบเงื่อนไข:

- event type เป็น `message`
- message type เป็น `text`
- source type เป็น `group` หรือ `room`
- มี mention ถึง OA ตัวเอง โดย `message.mention.mentionees[].isSelf === true`
- text มีคำสั่งที่รองรับ

คำสั่งเริ่ม:

```text
เริ่มเก็บข้อมูล
start collecting
start
```

คำสั่งหยุด:

```text
หยุดเก็บข้อมูล
stop collecting
stop
```

ตัวอย่างข้อความ:

```text
@ABBL Bot เริ่มเก็บข้อมูล
@ABBL Bot หยุดเก็บข้อมูล
```

หมายเหตุ: ไม่ควรตรวจจากชื่อ OA ด้วย string ธรรมดา เพราะชื่อเปลี่ยนได้และตำแหน่ง mention ใน LINE มี metadata อยู่แล้ว ให้ใช้ `isSelf` เป็นหลัก

## Event Handling Rules

### Join Event

เมื่อ OA ถูกเพิ่มเข้ากลุ่ม:

- สร้างหรือ update `line_chat_sources`
- ตั้ง `is_collecting = false`
- optional: reply ว่าให้แท็ก OA พร้อมคำสั่งเริ่มเก็บข้อมูล

### Leave Event

เมื่อ OA ออกจากกลุ่ม:

- ตั้ง `is_collecting = false`
- บันทึก `stopped_at`

### Text Message Event

ถ้าเป็นคำสั่งเริ่ม:

- upsert `line_chat_sources`
- ตั้ง `is_collecting = true`
- ตั้ง `started_by_user_id`
- ตั้ง `started_at = now()`
- reply ยืนยันว่าเริ่มเก็บข้อมูลแล้ว
- ไม่จำเป็นต้องเก็บ command message เป็น chat message หรือจะเก็บพร้อม flag เพิ่มก็ได้

ถ้าเป็นคำสั่งหยุด:

- ตั้ง `is_collecting = false`
- ตั้ง `stopped_by_user_id`
- ตั้ง `stopped_at = now()`
- reply ยืนยันว่าหยุดเก็บข้อมูลแล้ว

ถ้าไม่ใช่คำสั่ง:

- ตรวจ `line_chat_sources.is_collecting`
- ถ้า active ให้บันทึกลง `line_chat_messages`
- ถ้า inactive ให้ ignore

### Non-text Message Event

ช่วงแรกให้เก็บ metadata ได้ แต่ไม่ต้อง download content:

- `message_type`
- `message_id`
- `sender_user_id`
- `sent_at`
- `raw_event`

ถ้าต้องการเก็บรูป/ไฟล์ภายหลัง ให้ใช้ endpoint get content ด้วย `messageId` และเก็บไฟล์ใน storage

## Duplicate / Redelivery

LINE อาจส่ง webhook ซ้ำได้ถ้าครั้งก่อนรับไม่สำเร็จ จึงต้องป้องกัน duplicate:

- ใช้ `webhookEventId` ถ้ามีใน event
- ถ้าไม่มี ให้ fallback เป็น hash จาก `source_id + message_id + timestamp + type`
- ทำ unique constraint ที่ `line_chat_messages.webhook_event_id`
- Job ควรเป็น idempotent

## Suggested Laravel Classes

```text
app/Http/Controllers/LineWebhookController.php
app/Jobs/ProcessLineWebhookEvent.php
app/Services/Line/LineSignatureVerifier.php
app/Services/Line/LineCommandParser.php
app/Services/Line/LineMessagingClient.php
app/Models/LineChatSource.php
app/Models/LineChatMessage.php
database/migrations/*_create_line_chat_sources_table.php
database/migrations/*_create_line_chat_messages_table.php
database/migrations/*_create_line_webhook_logs_table.php
```

## Controller Responsibility

Controller ควรทำแค่:

- validate route secret ถ้ามี
- read raw body
- verify signature
- decode JSON หลัง verify
- dispatch job ต่อ event
- return JSON `{"ok": true}` พร้อม HTTP 200

ไม่ควรทำ:

- call LINE API นาน ๆ
- download content
- query หนัก
- parse JSON ก่อน verify

## Queue

ตั้งค่า queue ใน production เป็น database/redis แทน `sync`

```env
QUEUE_CONNECTION=database
```

คำสั่งที่ต้องใช้:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

## Reply Message

เมื่อต้องการตอบกลับในกลุ่ม ใช้ reply API:

```http
POST https://api.line.me/v2/bot/message/reply
Authorization: Bearer {LINE_CHANNEL_ACCESS_TOKEN}
Content-Type: application/json
```

Payload:

```json
{
    "replyToken": "{replyToken}",
    "messages": [
        {
            "type": "text",
            "text": "เริ่มเก็บข้อมูลในกลุ่มนี้แล้ว"
        }
    ]
}
```

ข้อควรระวัง:

- `replyToken` ใช้ได้ครั้งเดียวและหมดอายุเร็ว
- ถ้า job ทำงานช้าเกินไป อาจ reply ไม่ทัน ควร reply เฉพาะ command และ process ให้เร็ว
- ถ้าต้องส่งข้อความภายหลังต้องใช้ push message ซึ่งมี quota/cost

## Testing Checklist

### Local

- เขียน unit test สำหรับ `LineSignatureVerifier`
- เขียน unit test สำหรับ `LineCommandParser`
- เขียน feature test:
    - signature ถูกต้องและ `events` ว่าง ตอบ `200`
    - signature ผิด ตอบ `403`
    - mention OA พร้อมคำสั่งเริ่ม ทำให้ source active
    - inactive group ไม่เก็บข้อความทั่วไป
    - active group เก็บข้อความทั่วไป
    - redelivery event ไม่ทำให้ข้อมูลซ้ำ

### LINE Developers Console

1. เปิด Messaging API channel
2. ตั้ง Webhook URL เป็น HTTPS endpoint เช่น `https://example.com/line/webhook/{secret}`
3. Enable webhook
4. ปิด Auto-reply ถ้าไม่ต้องการให้ OA ตอบเองซ้อนกับระบบ
5. กด Verify webhook URL ต้องได้ success
6. เพิ่ม OA เข้ากลุ่ม LINE
7. ส่ง `@ชื่อOA เริ่มเก็บข้อมูล`
8. ส่งข้อความทั่วไปในกลุ่ม
9. ตรวจ database ว่าข้อความถูกบันทึก
10. ส่ง `@ชื่อOA หยุดเก็บข้อมูล`
11. ส่งข้อความต่อและตรวจว่าไม่ถูกบันทึก

## Security Notes

- ห้าม commit `.env` หรือ token ลง git
- อย่าใช้ IP allowlist เป็นหลัก เพราะ LINE ไม่เปิดเผย IP webhook source
- ต้อง verify signature ทุก request แม้ URL จะมี secret path
- log raw body เฉพาะช่วง debug และต้องระวังข้อมูลส่วนตัว
- จำกัดสิทธิ์หน้า admin/report ที่แสดงแชทที่เก็บมา
- เพิ่ม retention policy ถ้าข้อมูลแชทมีข้อมูลส่วนบุคคล

## Implementation Order

1. เพิ่ม env config ใน `config/services.php`
2. เพิ่ม route และยกเว้น CSRF สำหรับ `/line/webhook`
3. สร้าง migrations และ models
4. สร้าง `LineSignatureVerifier`
5. สร้าง `LineCommandParser`
6. สร้าง controller ให้ verify และ dispatch job
7. สร้าง job สำหรับ handle event
8. เพิ่ม reply client สำหรับตอบ command
9. เพิ่ม tests
10. ทดสอบจริงผ่าน LINE Developers Console

## Repository-specific Checklist

Checklist นี้ปรับจากสเปกด้านบนให้เข้ากับ repository นี้โดยตรง

### Repo Context

- โปรเจกต์นี้ใช้ Laravel `13.8` และ PHP `8.3`
- Test stack ใช้ PHPUnit ไม่ใช่ Pest
- มี migration สำหรับ `jobs`, `job_batches`, และ `failed_jobs` อยู่แล้ว จึงไม่ต้องสร้าง `queue:table` ซ้ำ
- `.env.example` ตั้ง `QUEUE_CONNECTION=database` อยู่แล้ว
- Route หลักอยู่ที่ `routes/web.php`
- API route เดิมอยู่ที่ `routes/api.php`
- CSRF exception ต้องตั้งใน `bootstrap/app.php`
- Model เดิมใช้ PHP attributes เช่น `#[Fillable]` และ method `casts(): array`

### Implementation Checklist

1. [x] เพิ่มค่าเหล่านี้ใน `.env.example` - updated 2026-07-03
    - `LINE_CHANNEL_SECRET=`
    - `LINE_CHANNEL_ACCESS_TOKEN=`
    - `LINE_WEBHOOK_ROUTE_SECRET=`
2. [x] เพิ่ม config `line` ใน `config/services.php` - updated 2026-07-03
3. [x] เพิ่ม route webhook ใน `routes/web.php` - updated 2026-07-03
    - `POST /line/webhook/{secret?}`
    - controller: `App\Http\Controllers\LineWebhookController`
    - route name: `line.webhook`
4. [x] เพิ่ม CSRF exception ใน `bootstrap/app.php` - updated 2026-07-03
    - `line/webhook`
    - `line/webhook/*`
5. [x] สร้าง migration `line_chat_sources` - updated 2026-07-03
    - unique index: `source_type`, `source_id`
6. [x] สร้าง migration `line_chat_messages` - updated 2026-07-03
    - foreign key ไปที่ `line_chat_sources.id`
    - unique index: `webhook_event_id`
    - index: `line_chat_source_id`, `sent_at`
    - index: `message_id`
    - index: `sender_user_id`
7. [x] สร้าง migration `line_webhook_logs` - updated 2026-07-03
    - ใช้สำหรับ debug ช่วงแรก
    - ไม่เก็บ raw body ถ้าไม่จำเป็น
8. [x] ไม่ต้องสร้าง migration queue เพิ่ม เพราะ repo มี `0001_01_01_000002_create_jobs_table.php` แล้ว - updated 2026-07-03
9. [x] สร้าง model `app/Models/LineChatSource.php` - updated 2026-07-03
    - ใช้ `#[Fillable]` ให้เข้ากับ model pattern เดิม
    - เพิ่ม cast `is_collecting`, `started_at`, `stopped_at`
10. [x] สร้าง model `app/Models/LineChatMessage.php` - updated 2026-07-03
    - ใช้ `#[Fillable]`
    - เพิ่ม relationship ไปที่ `LineChatSource`
    - เพิ่ม cast `raw_event`, `sent_at`
11. [x] สร้าง service `app/Services/Line/LineSignatureVerifier.php` - updated 2026-07-03
    - verify จาก raw body เท่านั้น
    - ใช้ `hash_hmac('sha256', $rawBody, $channelSecret, true)`
    - ใช้ `hash_equals`
12. [x] สร้าง service `app/Services/Line/LineCommandParser.php` - updated 2026-07-03
    - ตรวจ `message.mention.mentionees[].isSelf === true`
    - รองรับคำสั่งเริ่มและหยุดตามสเปก
    - ไม่ตรวจชื่อ OA ด้วย string ธรรมดา
13. [x] สร้าง service `app/Services/Line/LineMessagingClient.php` - updated 2026-07-03
    - ใช้ `LINE_CHANNEL_ACCESS_TOKEN`
    - reply เฉพาะ command ที่ต้องตอบกลับ
    - handle failure โดยไม่ทำให้ job ล้มถ้า LINE reply หมดอายุ
14. [x] สร้าง controller `app/Http/Controllers/LineWebhookController.php` - updated 2026-07-03
    - validate route secret ถ้ามี
    - อ่าน raw body ด้วย `$request->getContent()`
    - อ่าน signature แบบ case-insensitive ผ่าน header API ของ Laravel
    - verify signature ก่อน `json_decode`
    - log webhook summary ลง `line_webhook_logs`
    - dispatch job ทีละ event
    - ตอบ `{"ok": true}` พร้อม HTTP `200`
15. [x] Controller ต้องตอบ `403` และไม่ dispatch job เมื่อ signature ผิด - updated 2026-07-03
16. [x] Controller ต้องตอบ `200` เมื่อ `events` เป็น array ว่าง - updated 2026-07-03
17. [x] สร้าง job `app/Jobs/ProcessLineWebhookEvent.php` - updated 2026-07-03
    - handle `join`
    - handle `leave`
    - handle text command start/stop
    - handle normal text message
    - handle non-text message metadata
18. [x] Job ต้องทำงานแบบ idempotent - updated 2026-07-03
    - ใช้ `webhookEventId` ถ้ามี
    - fallback เป็น hash จาก `source_id`, `message_id`, `timestamp`, `type`
    - ไม่สร้าง message ซ้ำเมื่อ LINE redeliver
19. [x] เมื่อ start command สำเร็จ ให้ set `is_collecting = true` - updated 2026-07-03
20. [x] เมื่อ stop command สำเร็จ ให้ set `is_collecting = false` - updated 2026-07-03
21. [x] เมื่อ source inactive ให้ ignore ข้อความทั่วไป - updated 2026-07-03
22. [x] เมื่อ source active ให้บันทึกข้อความลง `line_chat_messages` - updated 2026-07-03
23. [x] เพิ่ม PHPUnit unit test `tests/Unit/LineSignatureVerifierTest.php` - updated 2026-07-03
24. [x] เพิ่ม PHPUnit unit test `tests/Unit/LineCommandParserTest.php` - updated 2026-07-03
25. [x] เพิ่ม PHPUnit feature test สำหรับ valid signature และ empty events ตอบ `200` - updated 2026-07-03
26. [x] เพิ่ม PHPUnit feature test สำหรับ invalid signature ตอบ `403` - updated 2026-07-03
27. [x] เพิ่ม PHPUnit feature test สำหรับ start command เปิด collecting - updated 2026-07-03
28. [x] เพิ่ม PHPUnit feature test สำหรับ inactive group ไม่เก็บข้อความ - updated 2026-07-03
29. [x] เพิ่ม PHPUnit feature test สำหรับ active group เก็บข้อความ - updated 2026-07-03
30. [x] เพิ่ม PHPUnit feature test สำหรับ duplicate/redelivery ไม่สร้างข้อมูลซ้ำ - updated 2026-07-03
31. [x] รัน `php artisan migrate` - updated 2026-07-03
32. [x] รัน `php artisan test` - updated 2026-07-03
33. [x] ตั้ง Webhook URL แบบ HTTPS ใน LINE Developers Console - updated 2026-07-06
34. [x] Enable webhook ใน LINE Developers Console - updated 2026-07-06
35. [ ] ปิด Auto-reply ถ้าไม่ต้องการให้ OA ตอบซ้อนกับระบบ
36. [ ] กด Verify webhook URL
37. [ ] เพิ่ม OA เข้ากลุ่ม LINE
38. [ ] ส่ง `@ชื่อOA เริ่มเก็บข้อมูล`
39. [ ] ส่งข้อความทั่วไป แล้วตรวจ `line_chat_messages`
40. [ ] ส่ง `@ชื่อOA หยุดเก็บข้อมูล`
41. [ ] ส่งข้อความต่อ แล้วตรวจว่าไม่มีข้อความใหม่ถูกบันทึก
42. [ ] รัน worker ใน environment จริงด้วย `php artisan queue:work`

### Test Results

ดูผลทดสอบล่าสุดที่ `docs/line-group-chat-webhook-test-results.md`
#######