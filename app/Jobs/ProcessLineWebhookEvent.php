<?php

namespace App\Jobs;

use App\Models\LineChatMessage;
use App\Models\LineChatSource;
use App\Services\Line\LineCommandParser;
use App\Services\Line\LineMessagingClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class ProcessLineWebhookEvent implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $event
     */
    public function __construct(public array $event)
    {
        //
    }

    public function handle(LineCommandParser $parser, LineMessagingClient $messagingClient): void
    {
        $source = $this->sourcePayload();

        if ($source === null) {
            return;
        }

        if (($this->event['type'] ?? null) === 'join') {
            $this->upsertSource($source, ['is_collecting' => false]);

            return;
        }

        if (($this->event['type'] ?? null) === 'leave') {
            $this->upsertSource($source, [
                'is_collecting' => false,
                'stopped_at' => now(),
            ]);

            return;
        }

        if (($this->event['type'] ?? null) !== 'message') {
            return;
        }

        $chatSource = $this->upsertSource($source);
        $command = $parser->parse($this->event);

        if ($command === LineCommandParser::START) {
            $chatSource->update([
                'is_collecting' => true,
                'started_by_user_id' => $source['user_id'],
                'started_at' => now(),
                'stopped_by_user_id' => null,
                'stopped_at' => null,
            ]);

            $messagingClient->replyText($this->event['replyToken'] ?? null, 'เริ่มเก็บข้อมูลในกลุ่มนี้แล้ว');

            return;
        }

        if ($command === LineCommandParser::STOP) {
            $chatSource->update([
                'is_collecting' => false,
                'stopped_by_user_id' => $source['user_id'],
                'stopped_at' => now(),
            ]);

            $messagingClient->replyText($this->event['replyToken'] ?? null, 'หยุดเก็บข้อมูลในกลุ่มนี้แล้ว');

            return;
        }

        if (! $chatSource->is_collecting) {
            return;
        }

        LineChatMessage::query()->firstOrCreate(
            ['webhook_event_id' => $this->webhookEventId()],
            [
                'line_chat_source_id' => $chatSource->id,
                'reply_token' => $this->event['replyToken'] ?? null,
                'message_id' => $this->event['message']['id'] ?? null,
                'message_type' => $this->event['message']['type'] ?? 'unknown',
                'text' => ($this->event['message']['type'] ?? null) === 'text' ? ($this->event['message']['text'] ?? null) : null,
                'sender_user_id' => $source['user_id'],
                'sent_at' => $this->sentAt(),
                'raw_event' => $this->event,
            ],
        );
    }

    /**
     * @return array{type: string, id: string, user_id: string|null}|null
     */
    private function sourcePayload(): ?array
    {
        $source = $this->event['source'] ?? [];
        $type = $source['type'] ?? null;

        if ($type === 'group' && isset($source['groupId'])) {
            return [
                'type' => 'group',
                'id' => $source['groupId'],
                'user_id' => $source['userId'] ?? null,
            ];
        }

        if ($type === 'room' && isset($source['roomId'])) {
            return [
                'type' => 'room',
                'id' => $source['roomId'],
                'user_id' => $source['userId'] ?? null,
            ];
        }

        return null;
    }

    /**
     * @param  array{type: string, id: string, user_id: string|null}  $source
     * @param  array<string, mixed>  $attributes
     */
    private function upsertSource(array $source, array $attributes = []): LineChatSource
    {
        return LineChatSource::query()->updateOrCreate(
            [
                'source_type' => $source['type'],
                'source_id' => $source['id'],
            ],
            $attributes,
        );
    }

    private function webhookEventId(): string
    {
        if (isset($this->event['webhookEventId']) && $this->event['webhookEventId'] !== '') {
            return $this->event['webhookEventId'];
        }

        $source = $this->sourcePayload();

        return hash('sha256', implode('|', [
            $source['id'] ?? '',
            $this->event['message']['id'] ?? '',
            $this->event['timestamp'] ?? '',
            $this->event['type'] ?? '',
        ]));
    }

    private function sentAt(): ?Carbon
    {
        if (! isset($this->event['timestamp']) || ! is_numeric($this->event['timestamp'])) {
            return null;
        }

        return Carbon::createFromTimestampMs((int) $this->event['timestamp']);
    }
}
