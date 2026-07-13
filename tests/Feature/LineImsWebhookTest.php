<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Issue;
use App\Models\LineChatSource;
use App\Models\User;
use App\Services\Line\Ims\LineContentDownloader;
use App\Services\Line\LineMessagingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class LineImsWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $channelSecret = 'test-line-secret';

    private string $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.line.channel_secret', $this->channelSecret);
        config()->set('services.line.webhook_route_secret', null);
        config()->set('services.line.ims.default_business_id', $this->businessId);
        config()->set('services.line.ims.system_user_id', 1);
        config()->set('services.line.ims.auto_submit', true);

        $this->seedBusiness();
        User::factory()->create(['id' => 1]);

        $this->mock(LineMessagingClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyChat')->andReturn(true);
            $mock->shouldReceive('replyText')->andReturn(true);
            $mock->shouldReceive('pushText')->andReturn(true);
        });
    }

    public function test_collecting_message_updates_form_state_title(): void
    {
        $this->startCollecting('group-ims-1');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-title',
                    'text' => 'ระบบ login ไม่ได้',
                    'groupId' => 'group-ims-1',
                    'messageId' => 'message-title',
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-1')->first();

        $this->assertSame('ระบบ login ไม่ได้', $source?->form_state['title']);
        $this->assertSame('ระบบ login ไม่ได้', $source?->draftIssue?->title);
    }

    public function test_auto_submit_when_form_complete(): void
    {
        $this->mock(LineMessagingClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyChat')->andReturn(true);
            $mock->shouldReceive('replyText')->andReturn(true);
            $mock->shouldReceive('pushText')->andReturn(true);
        });

        config()->set('services.line.ims.public_base_url', 'https://co-work.bluelane.co.th');

        $this->startCollecting('group-ims-2');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-complete-title',
                    'text' => 'ระบบล่ม',
                    'groupId' => 'group-ims-2',
                    'messageId' => 'message-complete-title',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-complete-url',
                    'text' => 'https://example.com/issue',
                    'groupId' => 'group-ims-2',
                    'messageId' => 'message-complete-url',
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-2')->first();
        $this->assertNull($source?->form_state['submitted_issue_id'] ?? null);

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-complete-stop',
                    'text' => '@ABBL Bot ยืนยัน',
                    'groupId' => 'group-ims-2',
                    'messageId' => 'message-complete-stop',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-2')->first();
        $submittedIssueId = $source?->form_state['submitted_issue_id'] ?? null;

        $this->assertNotNull($submittedIssueId);
        $this->assertDatabaseHas('issues', [
            'id' => $submittedIssueId,
            'status' => Issue::STATUS_PENDING,
            'title' => 'ระบบล่ม',
            'url' => 'https://example.com/issue',
        ]);
        $this->assertNull($source?->draft_issue_id);
    }

    public function test_no_url_intent_allows_submit_on_stop(): void
    {
        $this->startCollecting('group-ims-3');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-no-url-title',
                    'text' => 'printer ไม่ทำงาน',
                    'groupId' => 'group-ims-3',
                    'messageId' => 'message-no-url-title',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-no-url-intent',
                    'text' => 'ไม่มี url',
                    'groupId' => 'group-ims-3',
                    'messageId' => 'message-no-url-intent',
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-3')->first();

        $this->assertTrue($source?->form_state['no_url'] ?? false);
        $this->assertNull($source?->form_state['submitted_issue_id'] ?? null);

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-no-url-stop',
                    'text' => '@ABBL Bot เสร็จแล้ว',
                    'groupId' => 'group-ims-3',
                    'messageId' => 'message-no-url-stop',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-3')->first();

        $this->assertNotNull($source?->form_state['submitted_issue_id'] ?? null);
    }

    public function test_redelivery_does_not_reprocess_form_state(): void
    {
        $this->startCollecting('group-ims-4');

        $payload = [
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-redelivery-form',
                    'text' => 'หัวข้อเดิม',
                    'groupId' => 'group-ims-4',
                    'messageId' => 'message-redelivery-form',
                ]),
            ],
        ];

        $this->postSignedWebhook($payload)->assertOk();
        $this->postSignedWebhook($payload)->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-4')->first();

        $this->assertSame('หัวข้อเดิม', $source?->form_state['title']);
    }

    public function test_redelivery_after_submit_does_not_send_duplicate_success_messages(): void
    {
        $this->startCollecting('group-ims-redelivery-submit');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-redelivery-submit-title',
                    'text' => 'ปัญหา redelivery',
                    'groupId' => 'group-ims-redelivery-submit',
                    'messageId' => 'message-redelivery-submit-title',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-redelivery-submit-no-url',
                    'text' => 'ไม่มี url',
                    'groupId' => 'group-ims-redelivery-submit',
                    'messageId' => 'message-redelivery-submit-no-url',
                ]),
            ],
        ])->assertOk();

        $stopPayload = [
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-redelivery-submit-stop',
                    'text' => '@ABBL Bot ยืนยัน',
                    'groupId' => 'group-ims-redelivery-submit',
                    'messageId' => 'message-redelivery-submit-stop',
                    'mentionsSelf' => true,
                ]),
            ],
        ];

        $this->postSignedWebhook($stopPayload)->assertOk();
        $this->postSignedWebhook($stopPayload)->assertOk();

        $this->assertSame(1, Issue::query()->where('title', 'ปัญหา redelivery')->where('status', Issue::STATUS_PENDING)->count());
    }

    public function test_messages_after_submit_do_not_create_duplicate_issues(): void
    {
        $this->startCollecting('group-ims-dup');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-dup-title',
                    'text' => 'ปัญหาซ้ำ',
                    'groupId' => 'group-ims-dup',
                    'messageId' => 'message-dup-title',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-dup-url',
                    'text' => 'https://example.com/dup',
                    'groupId' => 'group-ims-dup',
                    'messageId' => 'message-dup-url',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-dup-submit',
                    'text' => '@ABBL Bot ยืนยัน',
                    'groupId' => 'group-ims-dup',
                    'messageId' => 'message-dup-submit',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-dup')->first();
        $firstSubmittedId = $source?->form_state['submitted_issue_id'] ?? null;

        $this->assertNotNull($firstSubmittedId);

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-dup-extra',
                    'text' => 'ข้อความหลังส่งแล้ว',
                    'groupId' => 'group-ims-dup',
                    'messageId' => 'message-dup-extra',
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-dup')->first();

        $this->assertSame($firstSubmittedId, $source?->form_state['submitted_issue_id'] ?? null);
        $this->assertSame(1, Issue::query()->where('title', 'ปัญหาซ้ำ')->where('status', Issue::STATUS_PENDING)->count());
    }

    public function test_image_message_adds_file_to_form_state(): void
    {
        $this->startCollecting('group-ims-5');

        $this->mock(LineContentDownloader::class, function (MockInterface $mock): void {
            $mock->shouldReceive('download')
                ->once()
                ->with('message-image-1', $this->businessId)
                ->andReturn("issue/{$this->businessId}/test.jpg");
        });

        $this->postSignedWebhook([
            'events' => [
                $this->imageEvent([
                    'webhookEventId' => 'event-image',
                    'groupId' => 'group-ims-5',
                    'messageId' => 'message-image-1',
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-5')->first();
        $source?->load('draftIssue.firstComment');

        $this->assertSame(
            ["issue/{$this->businessId}/test.jpg"],
            $source?->form_state['files'] ?? [],
        );
        $this->assertSame(
            ["issue/{$this->businessId}/test.jpg"],
            $source?->draftIssue?->firstComment?->files ?? [],
        );
    }

    public function test_stop_command_submits_complete_form_and_keeps_draft_when_incomplete(): void
    {
        config()->set('services.line.ims.auto_submit', false);

        $this->startCollecting('group-ims-7');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-stop-complete-title',
                    'text' => 'ปัญหาพร้อมส่ง',
                    'groupId' => 'group-ims-7',
                    'messageId' => 'message-stop-complete-title',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-stop-complete-url',
                    'text' => 'https://example.com/done',
                    'groupId' => 'group-ims-7',
                    'messageId' => 'message-stop-complete-url',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-stop-complete',
                    'text' => '@ABBL Bot หยุดเก็บข้อมูล',
                    'groupId' => 'group-ims-7',
                    'messageId' => 'message-stop-complete',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-7')->first();

        $this->assertFalse((bool) $source?->is_collecting);
        $this->assertNotNull($source?->form_state['submitted_issue_id'] ?? null);
    }

    public function test_finish_keyword_stops_collecting(): void
    {
        config()->set('services.line.ims.auto_submit', false);

        $this->startCollecting('group-ims-finish');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-finish-title',
                    'text' => 'ปัญหาพร้อมส่ง',
                    'groupId' => 'group-ims-finish',
                    'messageId' => 'message-finish-title',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-finish-url',
                    'text' => 'https://example.com/finish',
                    'groupId' => 'group-ims-finish',
                    'messageId' => 'message-finish-url',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-finish-stop',
                    'text' => '@ABBL Bot เสร็จแล้ว',
                    'groupId' => 'group-ims-finish',
                    'messageId' => 'message-finish-stop',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-finish')->first();

        $this->assertFalse((bool) $source?->is_collecting);
        $this->assertNotNull($source?->form_state['submitted_issue_id'] ?? null);
    }

    public function test_decline_confirmation_does_not_start_collecting(): void
    {
        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-decline-start',
                    'text' => '@ABBL Bot',
                    'groupId' => 'group-ims-decline',
                    'messageId' => 'message-decline-start',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-decline',
                    'text' => 'ไม่สร้าง',
                    'groupId' => 'group-ims-decline',
                    'messageId' => 'message-decline',
                    'mentionsSelf' => false,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-decline')->first();

        $this->assertFalse((bool) $source?->is_collecting);
        $this->assertNull($source?->draft_issue_id);
        $this->assertFalse($source?->form_state['awaiting_ims_confirmation'] ?? false);
    }

    public function test_mention_with_problem_text_is_saved_after_confirmation(): void
    {
        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-mention-problem',
                    'text' => '@ABBL Bot ระบบเข้าใช้งานไม่ได้ ตรวจสอบให้หน่อยครับ',
                    'groupId' => 'group-ims-mention-problem',
                    'messageId' => 'message-mention-problem',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-mention-problem')->first();

        $this->assertTrue($source?->form_state['awaiting_ims_confirmation'] ?? false);
        $this->assertSame(
            'ระบบเข้าใช้งานไม่ได้ ตรวจสอบให้หน่อยครับ',
            $source?->form_state['pending_initial_message'] ?? null,
        );

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-mention-problem-confirm',
                    'text' => 'สร้าง',
                    'groupId' => 'group-ims-mention-problem',
                    'messageId' => 'message-mention-problem-confirm',
                    'mentionsSelf' => false,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-mention-problem')->first();

        $this->assertTrue((bool) $source?->is_collecting);
        $this->assertSame('ระบบเข้าใช้งานไม่ได้ ตรวจสอบให้หน่อยครับ', $source?->form_state['title']);
        $this->assertArrayNotHasKey('pending_initial_message', $source?->form_state ?? []);
    }

    public function test_mention_with_problem_text_using_line_indexes_is_saved_after_confirmation(): void
    {
        $this->postSignedWebhook([
            'events' => [
                $this->mentionTextEvent([
                    'webhookEventId' => 'event-mention-index',
                    'text' => '@ABBL Automation ระบบเข้าใช้งานไม่ได้ ตรวจสอบให้หน่อยครับ',
                    'mentionLength' => 17,
                    'groupId' => 'group-ims-mention-index',
                    'messageId' => 'message-mention-index',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-mention-index-confirm',
                    'text' => 'สร้าง',
                    'groupId' => 'group-ims-mention-index',
                    'messageId' => 'message-mention-index-confirm',
                    'mentionsSelf' => false,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-mention-index')->first();

        $this->assertSame('ระบบเข้าใช้งานไม่ได้ ตรวจสอบให้หน่อยครับ', $source?->form_state['title']);
    }

    public function test_stop_command_keeps_pending_draft(): void
    {
        $this->startCollecting('group-ims-6');

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-stop-title',
                    'text' => 'draft ค้าง',
                    'groupId' => 'group-ims-6',
                    'messageId' => 'message-stop-title',
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-stop',
                    'replyToken' => 'reply-token-stop',
                    'text' => '@ABBL Bot หยุดเก็บข้อมูล',
                    'groupId' => 'group-ims-6',
                    'messageId' => 'message-stop',
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $source = LineChatSource::query()->where('source_id', 'group-ims-6')->first();

        $this->assertFalse((bool) $source?->is_collecting);
        $this->assertNotNull($source?->draft_issue_id);
        $this->assertSame('draft ค้าง', $source?->form_state['title']);
    }

    private function startCollecting(string $groupId): void
    {
        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => "event-start-{$groupId}",
                    'text' => '@ABBL Bot เริ่มเก็บข้อมูล',
                    'groupId' => $groupId,
                    'messageId' => "message-start-{$groupId}",
                    'mentionsSelf' => true,
                ]),
            ],
        ])->assertOk();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => "event-confirm-{$groupId}",
                    'text' => 'สร้าง',
                    'groupId' => $groupId,
                    'messageId' => "message-confirm-{$groupId}",
                    'mentionsSelf' => false,
                ]),
            ],
        ])->assertOk();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSignedWebhook(array $payload): TestResponse
    {
        $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = base64_encode(hash_hmac('sha256', $rawBody, $this->channelSecret, true));

        return $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_LINE_SIGNATURE' => $signature,
        ], $rawBody);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function mentionTextEvent(array $overrides = []): array
    {
        $text = $overrides['text'] ?? '@ABBL Bot hello';
        $mentionLength = $overrides['mentionLength'] ?? mb_strlen('@ABBL Bot');

        return [
            'type' => 'message',
            'webhookEventId' => $overrides['webhookEventId'] ?? 'event-id',
            'replyToken' => $overrides['replyToken'] ?? 'reply-token',
            'timestamp' => $overrides['timestamp'] ?? 1783069200000,
            'source' => [
                'type' => 'group',
                'groupId' => $overrides['groupId'] ?? 'group-id',
                'userId' => $overrides['userId'] ?? 'user-id',
            ],
            'message' => [
                'type' => 'text',
                'id' => $overrides['messageId'] ?? 'message-id',
                'text' => $text,
                'mention' => [
                    'mentionees' => [
                        [
                            'index' => 0,
                            'length' => $mentionLength,
                            'isSelf' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function textEvent(array $overrides = []): array
    {
        return [
            'type' => 'message',
            'webhookEventId' => $overrides['webhookEventId'] ?? 'event-id',
            'replyToken' => $overrides['replyToken'] ?? 'reply-token',
            'timestamp' => $overrides['timestamp'] ?? 1783069200000,
            'source' => [
                'type' => 'group',
                'groupId' => $overrides['groupId'] ?? 'group-id',
                'userId' => $overrides['userId'] ?? 'user-id',
            ],
            'message' => [
                'type' => 'text',
                'id' => $overrides['messageId'] ?? 'message-id',
                'text' => $overrides['text'] ?? 'hello',
                'mention' => [
                    'mentionees' => [
                        ['isSelf' => $overrides['mentionsSelf'] ?? false],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function imageEvent(array $overrides = []): array
    {
        return [
            'type' => 'message',
            'webhookEventId' => $overrides['webhookEventId'] ?? 'event-id',
            'replyToken' => $overrides['replyToken'] ?? 'reply-token',
            'timestamp' => $overrides['timestamp'] ?? 1783069200000,
            'source' => [
                'type' => 'group',
                'groupId' => $overrides['groupId'] ?? 'group-id',
                'userId' => $overrides['userId'] ?? 'user-id',
            ],
            'message' => [
                'type' => 'image',
                'id' => $overrides['messageId'] ?? 'message-id',
            ],
        ];
    }

    private function seedBusiness(): void
    {
        Business::unguarded(function (): void {
            Business::create([
                'id' => $this->businessId,
                'business_type' => 1,
                'business_vat_status' => 1,
                'business_branch_status' => 1,
                'business_branch_no' => 0,
                'business_branch_name' => 'สำนักงานใหญ่',
                'business_en_status' => 1,
                'business_name_en' => 'ABBL Automation Co-Work',
                'business_branch_no_en' => 0,
                'business_branch_name_en' => 'Head Office',
                'business_account_finance_year' => 12,
                'business_business_finance_year' => 12,
                'business_code' => 'ABBL',
                'business_name' => 'ABBL Automation Co-Work',
                'business_address1' => 'Bangkok',
                'business_status' => 1,
                'allow_issue' => true,
                'sales_target_amount' => 1000000.00,
            ]);
        });
    }
}
