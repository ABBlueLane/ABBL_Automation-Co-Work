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
            $mock->shouldReceive('replyText')->andReturnNull();
            $mock->shouldReceive('pushText')->andReturnNull();
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

    public function test_no_url_intent_allows_auto_submit(): void
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
