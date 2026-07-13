<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Models\LineChatMessage;
use App\Models\LineChatSource;
use App\Services\Line\Ims\LineImsFormProcessor;
use App\Services\Line\LineCommandParser;
use App\Services\Line\LineMessagingClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->overlapKey()))
                ->releaseAfter(5)
                ->expireAfter(60),
        ];
    }

    private function overlapKey(): string
    {
        $source = $this->event['source'] ?? [];

        return (string) ($source['groupId'] ?? $source['roomId'] ?? $this->event['webhookEventId'] ?? 'line-webhook');
    }

    public function handle(
        LineCommandParser $parser,
        LineMessagingClient $messagingClient,
        LineImsFormProcessor $formProcessor,
    ): void {
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

        $chatSource = $this->upsertSource($source)->fresh();
        $command = $parser->parse($this->event);

        if ($this->isAwaitingImsConfirmation($chatSource)) {
            if ($this->handleConfirmationReply($chatSource, $source, $parser, $formProcessor, $messagingClient)) {
                return;
            }
        }

        if ($command === LineCommandParser::STOP) {
            $chatSource->refresh();
            $submittedOnStop = $formProcessor->finalizeOnStop(
                $chatSource,
                $this->event['replyToken'] ?? null,
                $this->event['webhookEventId'] ?? null,
            );

            $chatSource->update([
                'is_collecting' => false,
                'stopped_by_user_id' => $source['user_id'],
                'stopped_at' => now(),
            ]);

            $stopMessage = 'หยุดเก็บข้อมูลในกลุ่มนี้แล้ว';

            if ($submittedOnStop) {
                $stopMessage .= "\nระบบส่งเข้า IMS แล้ว — กรุณาตรวจสอบลิงก์ด้านบน";
            } else {
                $stopMessage .= $this->draftStatusNotice($chatSource->fresh());
            }

            $messagingClient->notifyChat(
                $source['id'],
                $stopMessage,
                $this->event['replyToken'] ?? null,
            );

            return;
        }

        if (in_array($command, [LineCommandParser::START, LineCommandParser::MENTION], true)
            && ! $chatSource->is_collecting) {
            $this->askImsCreationConfirmation($chatSource, $source, $parser, $messagingClient);

            return;
        }

        if (! $chatSource->is_collecting) {
            return;
        }

        $message = LineChatMessage::query()->firstOrCreate(
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

        if (! $message->wasRecentlyCreated) {
            return;
        }

        if ($this->shouldProcessImsForm($chatSource->fresh())) {
            $formProcessor->process($chatSource->fresh(), $this->event, $message);
        }
    }

    private function shouldProcessImsForm(LineChatSource $chatSource): bool
    {
        return $chatSource->form_type === LineChatSource::FORM_TYPE_ISSUE_CREATE;
    }

    private function draftStatusNotice(LineChatSource $chatSource): string
    {
        $chatSource->loadMissing('draftIssue');

        if ($chatSource->draft_issue_id === null || $chatSource->draftIssue?->status !== Issue::STATUS_DRAFT) {
            return '';
        }

        $formState = $chatSource->form_state ?? [];
        $title = trim((string) ($formState['title'] ?? $chatSource->draftIssue?->title ?? ''));

        if ($title === '' || $title === 'แบบร่าง') {
            return "\n(มีแบบร่างค้างอยู่ — ยังไม่มีหัวข้อ)";
        }

        return "\n(มีแบบร่างค้างอยู่: {$title})";
    }

    /**
     * @param  array{type: string, id: string, user_id: string|null}  $source
     */
    private function askImsCreationConfirmation(
        LineChatSource $chatSource,
        array $source,
        LineCommandParser $parser,
        LineMessagingClient $messagingClient,
    ): void {
        $pendingMessage = $parser->pendingInitialMessageFromBody(
            (string) ($parser->extractMessageBody($this->event) ?? ''),
        );

        if ($pendingMessage !== null) {
            $this->storePendingInitialMessage($chatSource, $pendingMessage);
            $chatSource->refresh();
        } elseif (! $this->isAwaitingImsConfirmation($chatSource)) {
            $this->setAwaitingImsConfirmation($chatSource);
            $chatSource->refresh();
        }

        if ($chatSource->is_collecting) {
            $chatSource->update(['is_collecting' => false]);
            $chatSource->refresh();
        }

        $prompt = $pendingMessage !== null
            ? 'ได้รับข้อความแล้ว ต้องการสร้าง IMS หรือไม่? ตอบว่า สร้าง หรือ ไม่สร้าง'
            : 'ต้องการสร้าง IMS หรือไม่? ตอบว่า สร้าง หรือ ไม่สร้าง';

        $messagingClient->notifyChat(
            $source['id'],
            $prompt,
            $this->event['replyToken'] ?? null,
        );
    }

    /**
     * @param  array{type: string, id: string, user_id: string|null}  $source
     */
    private function handleConfirmationReply(
        LineChatSource $chatSource,
        array $source,
        LineCommandParser $parser,
        LineImsFormProcessor $formProcessor,
        LineMessagingClient $messagingClient,
    ): bool {
        if (($this->event['message']['type'] ?? null) !== 'text') {
            return false;
        }

        $confirmation = $parser->parseConfirmationReply((string) ($this->event['message']['text'] ?? ''));

        if ($confirmation === LineCommandParser::CONFIRM_CREATE) {
            $pendingMessage = $this->consumeConfirmationState($chatSource);

            $chatSource->update([
                'is_collecting' => true,
                'started_by_user_id' => $source['user_id'],
                'started_at' => now(),
                'stopped_by_user_id' => null,
                'stopped_at' => null,
            ]);

            $intro = $pendingMessage !== null
                ? 'กำลังเก็บข้อมูล บันทึกรายละเอียดที่แจ้งมาแล้ว แจ้งเพิ่มเติมได้เลย'
                : 'กำลังเก็บข้อมูล แจ้งรายละเอียดเข้ามาได้เลย';

            try {
                $formProcessor->initializeForm($chatSource->fresh());

                if ($pendingMessage !== null) {
                    $this->processPendingInitialMessage($chatSource->fresh(), $formProcessor, $pendingMessage);
                }
            } catch (\Throwable $exception) {
                Log::error('LINE IMS confirmation start failed.', [
                    'line_chat_source_id' => $chatSource->id,
                    'message' => $exception->getMessage(),
                ]);

                $messagingClient->notifyChat(
                    $source['id'],
                    'ไม่สามารถเริ่มเก็บข้อมูลได้: เกิดข้อผิดพลาดภายในระบบ',
                    $this->event['replyToken'] ?? null,
                );

                return true;
            }

            $messagingClient->notifyChat(
                $source['id'],
                implode("\n", [
                    $intro,
                    '',
                    'เมื่อแจ้งครบแล้ว @OA แล้วพิมพ์ เสร็จสิ้น เสร็จแล้ว หรือ ยืนยัน เพื่อหยุดรับข้อมูล',
                ]),
                $this->event['replyToken'] ?? null,
            );

            return true;
        }

        if ($confirmation === LineCommandParser::DECLINE_CREATE) {
            $this->consumeConfirmationState($chatSource);

            $messagingClient->notifyChat(
                $source['id'],
                'ยกเลิกการสร้าง IMS แล้ว',
                $this->event['replyToken'] ?? null,
            );

            return true;
        }

        $messagingClient->notifyChat(
            $source['id'],
            'ต้องการสร้าง IMS หรือไม่? ตอบว่า สร้าง หรือ ไม่สร้าง',
            $this->event['replyToken'] ?? null,
        );

        return true;
    }

    private function isAwaitingImsConfirmation(LineChatSource $chatSource): bool
    {
        return (bool) ($chatSource->form_state['awaiting_ims_confirmation'] ?? false);
    }

    private function setAwaitingImsConfirmation(LineChatSource $chatSource): void
    {
        $formState = $chatSource->form_state ?? [];
        $formState['awaiting_ims_confirmation'] = true;

        $chatSource->update(['form_state' => $formState]);
    }

    private function consumeConfirmationState(LineChatSource $chatSource): ?string
    {
        $chatSource->refresh();
        $formState = $chatSource->form_state ?? [];
        $message = trim((string) ($formState['pending_initial_message'] ?? ''));

        unset($formState['awaiting_ims_confirmation'], $formState['pending_initial_message']);

        $chatSource->update([
            'form_state' => $formState === [] ? null : $formState,
        ]);

        return $message !== '' ? $message : null;
    }

    private function storePendingInitialMessage(LineChatSource $chatSource, string $message): void
    {
        $formState = $chatSource->form_state ?? [];
        $formState['pending_initial_message'] = $message;
        $formState['awaiting_ims_confirmation'] = true;

        $chatSource->update(['form_state' => $formState]);
    }

    private function processPendingInitialMessage(
        LineChatSource $chatSource,
        LineImsFormProcessor $formProcessor,
        string $text,
    ): void {
        $message = LineChatMessage::query()->create([
            'line_chat_source_id' => $chatSource->id,
            'webhook_event_id' => 'pending-initial-'.$chatSource->id.'-'.hash('sha256', $text),
            'message_id' => null,
            'message_type' => 'text',
            'text' => $text,
            'sender_user_id' => $this->sourcePayload()['user_id'] ?? null,
            'sent_at' => $this->sentAt(),
            'raw_event' => $this->event,
        ]);

        $formProcessor->process($chatSource, [
            'message' => [
                'type' => 'text',
                'text' => $text,
            ],
            'replyToken' => null,
            'webhookEventId' => $message->webhook_event_id,
        ], $message);
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
