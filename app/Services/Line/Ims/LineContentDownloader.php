<?php

namespace App\Services\Line\Ims;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LineContentDownloader
{
    /**
     * @var array<string, string>
     */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm' => 'webm',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
    ];

    public function download(string $messageId, string $businessId): ?string
    {
        $accessToken = config('services.line.channel_access_token');

        if ($accessToken === null || $accessToken === '') {
            return null;
        }

        try {
            $response = Http::withToken($accessToken)
                ->withOptions(['stream' => true])
                ->get("https://api-data.line.me/v2/bot/message/{$messageId}/content")
                ->throw();

            $contentType = strtolower((string) $response->header('Content-Type', 'application/octet-stream'));
            $contentType = strtok($contentType, ';') ?: 'application/octet-stream';

            if (! $this->isAllowedMimeType($contentType)) {
                Log::warning('LINE content download rejected mime type.', [
                    'message_id' => $messageId,
                    'content_type' => $contentType,
                ]);

                return null;
            }

            $extension = self::EXTENSIONS[$contentType] ?? 'bin';
            $filename = Str::uuid().'.'.$extension;
            $path = "issue/{$businessId}/{$filename}";

            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (RequestException $exception) {
            Log::warning('LINE content download failed.', [
                'message_id' => $messageId,
                'status' => $exception->response?->status(),
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function isAllowedMimeType(string $contentType): bool
    {
        $allowed = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'video/mp4',
            'video/quicktime',
            'video/webm',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
            'text/markdown',
            'text/html',
            'application/json',
            'application/xml',
            'text/css',
            'application/javascript',
            'text/javascript',
        ];

        return in_array($contentType, $allowed, true);
    }
}
