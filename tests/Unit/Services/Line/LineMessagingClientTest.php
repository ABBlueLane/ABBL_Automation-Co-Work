<?php

namespace Tests\Unit\Services\Line;

use App\Services\Line\LineMessagingClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LineMessagingClientTest extends TestCase
{
    public function test_notify_chat_prefers_reply_when_token_is_available(): void
    {
        config()->set('services.line.channel_access_token', 'test-token');

        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([]),
            'https://api.line.me/v2/bot/message/push' => Http::response([]),
        ]);

        $client = new LineMessagingClient;
        $result = $client->notifyChat('group-123', 'hello', 'reply-token');

        $this->assertTrue($result);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.line.me/v2/bot/message/reply');
    }

    public function test_notify_chat_falls_back_to_push_when_reply_fails(): void
    {
        config()->set('services.line.channel_access_token', 'test-token');

        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response(['message' => 'Invalid reply token'], 400),
            'https://api.line.me/v2/bot/message/push' => Http::response([]),
        ]);

        $client = new LineMessagingClient;
        $result = $client->notifyChat('group-123', 'hello', 'reply-token');

        $this->assertTrue($result);
        Http::assertSentCount(2);
    }
}
