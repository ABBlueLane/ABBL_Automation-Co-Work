<?php

namespace App\Services\Line;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingClient
{
    /**
     * Send a message to a group/room via push (preferred), falling back to reply token.
     */
    public function notifyChat(string $to, string $text, ?string $replyToken = null): bool
    {
        if ($to !== '' && $this->pushText($to, $text)) {
            return true;
        }

        return $this->replyText($replyToken, $text);
    }

    public function replyText(?string $replyToken, string $text): bool
    {
        $accessToken = config('services.line.channel_access_token');

        if ($replyToken === null || $replyToken === '' || $accessToken === null || $accessToken === '') {
            return false;
        }

        return $this->sendTextMessages($accessToken, 'https://api.line.me/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages' => $this->textMessages($text),
        ], 'LINE reply failed.');
    }

    public function pushText(string $to, string $text): bool
    {
        $accessToken = config('services.line.channel_access_token');

        if ($to === '' || $accessToken === null || $accessToken === '') {
            Log::warning('LINE push skipped: missing destination or access token.', [
                'to' => $to,
            ]);

            return false;
        }

        return $this->sendTextMessages($accessToken, 'https://api.line.me/v2/bot/message/push', [
            'to' => $to,
            'messages' => $this->textMessages($text),
        ], 'LINE push failed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendTextMessages(string $accessToken, string $url, array $payload, string $logContext): bool
    {
        try {
            Http::withToken($accessToken)
                ->acceptJson()
                ->post($url, $payload)
                ->throw();

            return true;
        } catch (RequestException $exception) {
            $response = $exception->response?->json();
            $isInvalidReplyToken = $url === 'https://api.line.me/v2/bot/message/reply'
                && $exception->response?->status() === 400
                && (($response['message'] ?? '') === 'Invalid reply token');

            if ($isInvalidReplyToken) {
                Log::info('LINE reply token expired or already used — use push instead.', [
                    'to' => $payload['to'] ?? null,
                ]);

                return false;
            }

            Log::warning($logContext, [
                'status' => $exception->response?->status(),
                'message' => $exception->getMessage(),
                'response' => $response,
                'to' => $payload['to'] ?? null,
            ]);

            return false;
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
