<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class IssueMockSeeder extends Seeder
{
    private const MOCK_PREFIX = 'MOCK-IMS';

    /**
     * Seed mock issues for pagination testing.
     */
    public function run(): void
    {
        $user = User::query()->where('status', 'active')->first();
        if (! $user) {
            $this->command?->error('ไม่พบผู้ใช้งาน active กรุณารัน DatabaseSeeder ก่อน');

            return;
        }

        $businesses = Business::query()
            ->where('business_status', 1)
            ->orderBy('business_name')
            ->get();

        if ($businesses->isEmpty()) {
            $this->command?->error('ไม่พบธุรกิจในระบบ กรุณารัน BusinessSeeder ก่อน');

            return;
        }

        $titles = [
            'Login System Error',
            'Payment Gateway Timeout',
            'UI Display Glitch on Mobile',
            'Database Connection Failure',
            'Email Notification Delay',
            'Report Export ไม่สมบูรณ์',
            'API Response ช้าเกินกำหนด',
            'หน้า Dashboard โหลดไม่ขึ้น',
            'ไฟล์แนบอัปโหลดไม่ได้',
            'สิทธิ์ผู้ใช้งานไม่ถูกต้อง',
            'ปุ่ม Submit ไม่ทำงาน',
            'ข้อมูลซ้ำในรายงาน',
            'Session หลุดบ่อย',
            'Search Filter คืนผลผิด',
            'LINE Notification ไม่ส่ง',
            'PDF Preview แสดงผลเพี้ยน',
            'Calendar Sync ล้มเหลว',
            'Webhook Callback Error',
            'Cache ไม่อัปเดตหลังแก้ไข',
            'Mobile Menu ทับซ้อนเนื้อหา',
        ];

        $descriptions = [
            'The application is failing to process user login requests. Multiple users reported authentication errors since this morning.',
            'Transactions are timing out during checkout. The payment provider API returns HTTP 504 intermittently.',
            'Layout breaks on screens smaller than 768px. Navigation menu overlaps main content on iOS Safari.',
            'Production database pool exhausted during peak hours. Connection retry logic needs investigation.',
            'Queued emails are delayed by more than 30 minutes. SMTP relay appears healthy but jobs are stuck.',
            'Exported Excel files are missing the last two columns. Issue occurs only for filtered date ranges.',
            'Average API latency increased from 200ms to 2.5s after the latest deployment.',
            'Dashboard widgets show infinite loading spinner for admin users with multi-business access.',
            'File uploads fail silently for attachments larger than 5MB. No validation message is shown.',
            'Staff users can access modules outside their assigned role after session refresh.',
            'Submit button stays disabled after form validation passes on the issue create page.',
            'Monthly summary report counts duplicate records from merged business units.',
            'Users are logged out every 10 minutes despite remember-me being enabled.',
            'Keyword search returns issues from other businesses when business filter is applied.',
            'LINE push notifications for issue updates are not delivered to subscribed groups.',
            'PDF preview renders Thai characters as boxes in Chrome on Windows.',
            'Google Calendar sync fails with OAuth token expired error for shared calendars.',
            'Incoming webhook returns 500 when payload contains nested attachment metadata.',
            'Updated issue status does not reflect on list view until hard refresh.',
            'Hamburger menu covers page header on mobile viewport after recent CSS changes.',
        ];

        $statuses = [
            Issue::STATUS_PENDING,
            Issue::STATUS_IN_PROGRESS,
            Issue::STATUS_WAITING_REVIEW,
            Issue::STATUS_CUSTOMER_REPLIED,
            Issue::STATUS_DONE,
        ];

        $priorities = [
            Issue::PRIORITY_HIGH,
            Issue::PRIORITY_MEDIUM,
            Issue::PRIORITY_LOW,
        ];

        $created = 0;

        for ($i = 1; $i <= 20; $i++) {
            $issueNumber = sprintf('%s-%06d', self::MOCK_PREFIX, $i);

            if (Issue::query()->where('issue_number', $issueNumber)->exists()) {
                $this->command?->warn("ข้าม {$issueNumber} — มีข้อมูลอยู่แล้ว");

                continue;
            }

            $business = $businesses[($i - 1) % $businesses->count()];
            $index = $i - 1;

            $issue = Issue::create([
                'business_id' => $business->id,
                'issue_number' => $issueNumber,
                'title' => $titles[$index],
                'status' => $statuses[$index % count($statuses)],
                'priority' => $priorities[$index % count($priorities)],
                'created_by' => $user->id,
                'assigned_to' => $index % 3 === 0 ? $user->id : null,
                'created_at' => now()->subDays(20 - $i),
                'updated_at' => now()->subDays(20 - $i),
            ]);

            IssueComment::create([
                'issue_id' => $issue->id,
                'user_id' => $user->id,
                'comment' => $descriptions[$index],
                'files' => [],
            ]);

            $commentCount = ($index % 4) + 1;
            for ($c = 1; $c < $commentCount; $c++) {
                IssueComment::create([
                    'issue_id' => $issue->id,
                    'user_id' => $user->id,
                    'comment' => "ความคิดเห็นเพิ่มเติม #{$c} สำหรับ {$issueNumber}",
                    'files' => [],
                ]);
            }

            $created++;
        }

        $this->command?->info("สร้าง mock issue สำเร็จ {$created} รายการ (prefix: ".self::MOCK_PREFIX.')');
    }
}
