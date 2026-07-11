<?php

namespace Tests\Unit\Services\Line;

use App\Services\Line\LineMessagingClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LineMessagingClientTest extends TestCase
{
    public function test_notify_chat_prefers_push_over_reply(): void
    {
        config()->set('services.line.channel_access_token', 'test-token');

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([]),
            'https://api.line.me/v2/bot/message/reply' => Http::response([]),
        ]);

        $client = new LineMessagingClient;
        $result = $client->notifyChat('group-123', 'hello', 'reply-token');

        $this->assertTrue($result);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.line.me/v2/bot/message/push');
    }

    public function test_notify_chat_falls_back_to_reply_when_push_fails(): void
    {
        config()->set('services.line.channel_access_token', 'test-token');

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response(['message' => 'failed'], 500),
            'https://api.line.me/v2/bot/message/reply' => Http::response([]),
        ]);

        $client = new LineMessagingClient;
        $result = $client->notifyChat('group-123', 'hello', 'reply-token');

        $this->assertTrue($result);
        Http::assertSentCount(2);
    }
}
