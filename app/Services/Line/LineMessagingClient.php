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

        try {
            Http::withToken($accessToken)
                ->acceptJson()
                ->post('https://api.line.me/v2/bot/message/reply', [
                    'replyToken' => $replyToken,
                    'messages' => [
                        [
                            'type' => 'text',
                            'text' => $text,
                        ],
                    ],
                ])
                ->throw();
        } catch (RequestException $exception) {
            Log::warning('LINE reply failed.', [
                'status' => $exception->response?->status(),
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
