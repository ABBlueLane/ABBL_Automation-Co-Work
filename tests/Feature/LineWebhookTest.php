<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\LineChatMessage;
use App\Models\LineChatSource;
use App\Models\User;
use App\Services\Line\LineMessagingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $channelSecret = 'test-line-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.line.channel_secret', $this->channelSecret);
        config()->set('services.line.webhook_route_secret', null);
    }

    public function test_valid_signature_with_empty_events_returns_ok(): void
    {
        $this->postSignedWebhook(['destination' => 'bot-user-id', 'events' => []])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('line_webhook_logs', [
            'signature_valid' => true,
            'destination' => 'bot-user-id',
            'event_count' => 0,
        ]);
    }

    public function test_invalid_signature_returns_forbidden(): void
    {
        $this->postJson('/line/webhook', ['events' => []], ['x-line-signature' => 'invalid'])
            ->assertForbidden()
            ->assertJson(['message' => 'Invalid signature']);

        $this->assertDatabaseHas('line_webhook_logs', [
            'signature_valid' => false,
            'error_message' => 'Invalid LINE signature.',
        ]);
    }

    public function test_start_command_makes_source_active(): void
    {
        $this->mock(LineMessagingClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyChat')->andReturn(true);
            $mock->shouldReceive('replyText')->andReturn(true);
            $mock->shouldReceive('pushText')->andReturn(true);
        });

        $this->seedImsDefaults();

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-start',
                    'replyToken' => 'reply-token-start',
                    'text' => '@ABBL Bot เริ่มเก็บข้อมูล',
                    'groupId' => 'group-1',
                    'userId' => 'user-1',
                    'messageId' => 'message-start',
                ]),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('line_chat_sources', [
            'source_type' => 'group',
            'source_id' => 'group-1',
            'is_collecting' => false,
        ]);

        $source = LineChatSource::query()->where('source_id', 'group-1')->first();
        $this->assertTrue($source?->form_state['awaiting_ims_confirmation'] ?? false);

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-confirm',
                    'replyToken' => 'reply-token-confirm',
                    'text' => 'สร้าง',
                    'groupId' => 'group-1',
                    'userId' => 'user-1',
                    'messageId' => 'message-confirm',
                    'mentionsSelf' => false,
                ]),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('line_chat_sources', [
            'source_type' => 'group',
            'source_id' => 'group-1',
            'is_collecting' => true,
            'started_by_user_id' => 'user-1',
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
        ]);

        $source = LineChatSource::query()->where('source_id', 'group-1')->first();
        $this->assertNotNull($source?->draft_issue_id);
        $this->assertNotNull($source?->form_state);
        $this->assertFalse($source?->form_state['awaiting_ims_confirmation'] ?? false);
    }

    public function test_inactive_group_does_not_store_general_message(): void
    {
        LineChatSource::query()->create([
            'source_type' => 'group',
            'source_id' => 'group-1',
            'is_collecting' => false,
        ]);

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-message',
                    'text' => 'hello',
                    'groupId' => 'group-1',
                    'messageId' => 'message-1',
                    'mentionsSelf' => false,
                ]),
            ],
        ])->assertOk();

        $this->assertDatabaseCount('line_chat_messages', 0);
    }

    public function test_active_group_stores_general_message(): void
    {
        $this->seedImsDefaults();

        LineChatSource::query()->create([
            'source_type' => 'group',
            'source_id' => 'group-1',
            'is_collecting' => true,
            'business_id' => '9c9aafbc-f74a-4e30-b44a-1209b30431ad',
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
            'form_state' => LineChatSource::defaultIssueCreateFormState(),
        ]);

        $this->mock(LineMessagingClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyChat')->andReturn(true);
            $mock->shouldReceive('replyText')->andReturn(true);
        });

        $this->postSignedWebhook([
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-message',
                    'text' => 'hello',
                    'groupId' => 'group-1',
                    'userId' => 'user-1',
                    'messageId' => 'message-1',
                ]),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('line_chat_messages', [
            'webhook_event_id' => 'event-message',
            'message_id' => 'message-1',
            'message_type' => 'text',
            'text' => 'hello',
            'sender_user_id' => 'user-1',
        ]);
    }

    public function test_redelivery_event_does_not_create_duplicate_message(): void
    {
        LineChatSource::query()->create([
            'source_type' => 'group',
            'source_id' => 'group-1',
            'is_collecting' => true,
        ]);

        $payload = [
            'events' => [
                $this->textEvent([
                    'webhookEventId' => 'event-redelivery',
                    'text' => 'same message',
                    'groupId' => 'group-1',
                    'messageId' => 'message-1',
                ]),
            ],
        ];

        $this->postSignedWebhook($payload)->assertOk();
        $this->postSignedWebhook($payload)->assertOk();

        $this->assertSame(1, LineChatMessage::query()->where('webhook_event_id', 'event-redelivery')->count());
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
        $mentionsSelf = $overrides['mentionsSelf'] ?? true;

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
                        ['isSelf' => $mentionsSelf],
                    ],
                ],
            ],
        ];
    }

    private function seedImsDefaults(): void
    {
        $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

        config()->set('services.line.ims.default_business_id', $businessId);
        config()->set('services.line.ims.system_user_id', 1);
        config()->set('services.line.ims.auto_submit', false);

        Business::unguarded(function () use ($businessId): void {
            Business::query()->firstOrCreate(
                ['id' => $businessId],
                [
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
                ],
            );
        });

        User::factory()->create(['id' => 1]);
    }
}
