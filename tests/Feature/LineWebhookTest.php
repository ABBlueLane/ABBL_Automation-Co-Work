<?php

namespace Tests\Feature;

use App\Models\LineChatMessage;
use App\Models\LineChatSource;
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
            $mock->shouldReceive('replyText')
                ->once()
                ->with('reply-token-start', 'เริ่มเก็บข้อมูลในกลุ่มนี้แล้ว');
        });

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
            'is_collecting' => true,
            'started_by_user_id' => 'user-1',
        ]);
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
        LineChatSource::query()->create([
            'source_type' => 'group',
            'source_id' => 'group-1',
            'is_collecting' => true,
        ]);

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
}
