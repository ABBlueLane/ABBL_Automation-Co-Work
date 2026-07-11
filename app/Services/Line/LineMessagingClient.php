<?php

namespace App\Services\Line;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingClient
{
    public function replyText(?string $replyToken, string $text): void
    {
        $accessToken = config('services.line.channel_access_token');

        if ($replyToken === null || $replyToken === '' || $accessToken === null || $accessToken === '') {
            return;
        }

        $this->sendTextMessages($accessToken, 'https://api.line.me/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages' => $this->textMessages($text),
        ], 'LINE reply failed.');
    }

    public function pushText(string $to, string $text): void
    {
        $accessToken = config('services.line.channel_access_token');

        if ($to === '' || $accessToken === null || $accessToken === '') {
            return;
        }

        $this->sendTextMessages($accessToken, 'https://api.line.me/v2/bot/message/push', [
            'to' => $to,
            'messages' => $this->textMessages($text),
        ], 'LINE push failed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendTextMessages(string $accessToken, string $url, array $payload, string $logContext): void
    {
        try {
            Http::withToken($accessToken)
                ->acceptJson()
                ->post($url, $payload)
                ->throw();
        } catch (RequestException $exception) {
            Log::warning($logContext, [
                'status' => $exception->response?->status(),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return list<array{type: string, text: string}>
     */
    private function textMessages(string $text): array
    {
        return [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];
    }
}
