<?php

namespace App\Services\Line\Ims;

use App\Models\Issue;
use App\Models\LineChatMessage;
use App\Models\LineChatSource;
use App\Models\LineImsSubmission;
use App\Services\Line\LineMessagingClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LineImsFormProcessor
{
    public function __construct(
        private readonly IssueSubmissionService $submissionService,
        private readonly IssueCreateFormMapper $mapper,
        private readonly IssueCreateFormCompleter $completer,
        private readonly LineContentDownloader $contentDownloader,
        private readonly LineMessagingClient $messagingClient,
    ) {}

    /**
     * @param  array<string, mixed>  $event
     */
    public function process(LineChatSource $chatSource, array $event, ?LineChatMessage $message = null): void
    {
        if ($chatSource->form_type !== LineChatSource::FORM_TYPE_ISSUE_CREATE) {
            return;
        }

        $chatSource = $this->ensureChatSourceConfigured($chatSource);
        $formState = $chatSource->form_state ?? LineChatSource::defaultIssueCreateFormState();
        $replyToken = $event['replyToken'] ?? $message?->reply_token;
        $messageType = $event['message']['type'] ?? $message?->message_type ?? 'unknown';
        $messageId = $event['message']['id'] ?? $message?->message_id;
        $webhookEventId = $event['webhookEventId'] ?? $message?->webhook_event_id;

        if ($messageId !== null) {
            $formState['last_message_id'] = $messageId;
        }

        if ($messageType === 'text') {
            $text = (string) ($event['message']['text'] ?? $message?->text ?? '');
            $mapped = $this->mapper->mapTextMessage($text, $formState);

            if ($mapped['action'] === IssueCreateFormMapper::ACTION_RESET) {
                $this->resetForm($chatSource, $replyToken);

                return;
            }

            if ($mapped['action'] === IssueCreateFormMapper::ACTION_SUBMIT) {
                $formState = $this->mergeFormState($formState, $mapped['updates']);
                $this->persistFormState($chatSource, $formState);
                $this->attemptSubmit($chatSource, $formState, $replyToken, $webhookEventId, force: true);

                return;
            }

            $formState = $this->mergeFormState($formState, $mapped['updates']);
        } elseif (in_array($messageType, ['image', 'file', 'video'], true)) {
            $path = $messageId
                ? $this->contentDownloader->download($messageId, (string) $chatSource->business_id)
                : null;

            if ($path !== null) {
                $files = (array) ($formState['files'] ?? []);
                $files[] = $path;
                $formState['files'] = $files;
            }
        } elseif ($messageType === 'location') {
            $location = $event['message'] ?? [];
            $label = trim((string) ($location['address'] ?? $location['title'] ?? 'ตำแหน่ง'));
            $lat = $location['latitude'] ?? null;
            $lng = $location['longitude'] ?? null;
            $locationText = trim("{$label} ({$lat}, {$lng})");
            $formState['comment'] = $this->appendComment((string) ($formState['comment'] ?? ''), $locationText);
        } elseif ($messageType === 'sticker') {
            $this->messagingClient->replyText($replyToken, 'ไม่รองรับสติกเกอร์เป็นข้อมูลแจ้งปัญหา กรุณาส่งข้อความหรือรูปภาพ');

            return;
        } else {
            return;
        }

        $formState = $this->completer->applyMissingFields($formState);
        $chatSource = $this->persistFormState($chatSource, $formState);
        $this->syncDraftIssue($chatSource, $formState);

        if ($this->completer->isComplete($formState) && $this->shouldAutoSubmit()) {
            $this->attemptSubmit($chatSource, $formState, $replyToken, $webhookEventId);

            return;
        }

        $this->replyStatus($replyToken, $formState);
    }

    public function initializeForm(LineChatSource $chatSource): LineChatSource
    {
        $businessId = config('services.line.ims.default_business_id');
        $formState = LineChatSource::defaultIssueCreateFormState();

        $chatSource->update([
            'business_id' => $businessId,
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
            'form_state' => $formState,
            'draft_issue_id' => null,
        ]);

        $draft = $this->submissionService->createOrUpdateDraft(
            (string) $businessId,
            $this->systemUserId(),
            $this->draftPayloadFromFormState($formState),
        );

        $chatSource->update(['draft_issue_id' => $draft->id]);

        return $chatSource->fresh();
    }

    /**
     * Submit pending IMS draft when user stops collecting, if the form is complete.
     *
     * @return bool True when an issue was submitted to IMS.
     */
    public function finalizeOnStop(LineChatSource $chatSource, ?string $replyToken, ?string $webhookEventId): bool
    {
        if ($chatSource->form_type !== null && $chatSource->form_type !== LineChatSource::FORM_TYPE_ISSUE_CREATE) {
            return false;
        }

        $chatSource = $this->ensureChatSourceConfigured($chatSource);
        $formState = $chatSource->form_state ?? LineChatSource::defaultIssueCreateFormState();

        if (! $this->completer->isComplete($formState)) {
            return false;
        }

        $this->attemptSubmit($chatSource, $formState, $replyToken, $webhookEventId);

        return LineImsSubmission::query()
            ->where('webhook_event_id', $webhookEventId)
            ->where('status', LineImsSubmission::STATUS_SUCCESS)
            ->exists();
    }

    public function successMessage(Issue $issue, string $businessId): string
    {
        $viewUrl = $this->issueViewUrl($businessId, $issue->id);

        return implode("\n", [
            'แจ้งปัญหาสำเร็จ — ระบบเปิด IMS แล้ว',
            "Issue #{$issue->issue_number}",
            '',
            'กรุณาตรวจสอบและรีวิวรายละเอียด:',
            $viewUrl,
        ]);
    }

    public function issueViewUrl(string $businessId, int $issueId): string
    {
        $baseUrl = rtrim((string) config('services.line.ims.public_base_url', config('app.url')), '/');

        return "{$baseUrl}/issue/{$businessId}/view/{$issueId}";
    }

    private function notifyIssueSubmittedToGroup(LineChatSource $chatSource, Issue $issue, ?string $replyToken): void
    {
        $message = $this->successMessage($issue, (string) $chatSource->business_id);
        $groupId = trim((string) $chatSource->source_id);

        if ($groupId === '') {
            Log::warning('LINE IMS notify skipped: missing group id.', [
                'line_chat_source_id' => $chatSource->id,
                'issue_id' => $issue->id,
            ]);
            $this->messagingClient->replyText($replyToken, $message);

            return;
        }

        $pushed = $this->messagingClient->pushText($groupId, $message);

        if (! $pushed) {
            Log::warning('LINE IMS push failed, falling back to reply.', [
                'line_chat_source_id' => $chatSource->id,
                'group_id' => $groupId,
                'issue_id' => $issue->id,
            ]);
            $this->messagingClient->replyText($replyToken, $message);
        }
    }

    private function ensureChatSourceConfigured(LineChatSource $chatSource): LineChatSource
    {
        $businessId = $chatSource->business_id ?: config('services.line.ims.default_business_id');

        if ($chatSource->business_id === null) {
            $chatSource->update(['business_id' => $businessId]);
            $chatSource->refresh();
        }

        if ($chatSource->form_type === null) {
            $chatSource->update(['form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE]);
            $chatSource->refresh();
        }

        if ($chatSource->form_state === null) {
            $chatSource->update(['form_state' => LineChatSource::defaultIssueCreateFormState()]);
            $chatSource->refresh();
        }

        if ($chatSource->draft_issue_id === null) {
            $draft = $this->submissionService->createOrUpdateDraft(
                (string) $chatSource->business_id,
                $this->systemUserId(),
                $this->draftPayloadFromFormState($chatSource->form_state ?? []),
            );
            $chatSource->update(['draft_issue_id' => $draft->id]);
            $chatSource->refresh();
        }

        return $chatSource;
    }

    /**
     * @param  array<string, mixed>  $formState
     */
    private function syncDraftIssue(LineChatSource $chatSource, array $formState): void
    {
        $draft = $chatSource->draftIssue;

        if ($draft === null || $draft->status !== Issue::STATUS_DRAFT) {
            $draft = $this->submissionService->createOrUpdateDraft(
                (string) $chatSource->business_id,
                $this->systemUserId(),
                $this->draftPayloadFromFormState($formState),
            );
            $chatSource->update(['draft_issue_id' => $draft->id]);

            return;
        }

        $this->submissionService->createOrUpdateDraft(
            (string) $chatSource->business_id,
            $this->systemUserId(),
            $this->draftPayloadFromFormState($formState),
            $draft,
        );
    }

    /**
     * @param  array<string, mixed>  $formState
     */
    private function attemptSubmit(
        LineChatSource $chatSource,
        array $formState,
        ?string $replyToken,
        ?string $webhookEventId,
        bool $force = false,
    ): void {
        if (! $this->completer->isComplete($formState)) {
            $missing = implode(', ', $formState['missing_fields'] ?? $this->completer->missingFields($formState));
            $this->messagingClient->replyText($replyToken, "ยังส่งไม่ได้ — ข้อมูลยังไม่ครบ: {$missing}");

            return;
        }

        $draft = $chatSource->draftIssue;

        if ($draft === null || $draft->status !== Issue::STATUS_DRAFT) {
            $this->messagingClient->replyText($replyToken, 'ไม่พบแบบร่างสำหรับส่งเข้าระบบ');

            return;
        }

        if ($webhookEventId !== null && $webhookEventId !== '') {
            $existingSubmission = LineImsSubmission::query()
                ->where('webhook_event_id', $webhookEventId)
                ->exists();

            if ($existingSubmission) {
                return;
            }
        }

        $audit = LineImsSubmission::query()->create([
            'line_chat_source_id' => $chatSource->id,
            'draft_issue_id' => $draft->id,
            'webhook_event_id' => $webhookEventId,
            'status' => LineImsSubmission::STATUS_PENDING,
            'form_state' => $formState,
        ]);

        try {
            $this->syncDraftIssue($chatSource, $formState);

            $submitted = $this->submissionService->submitDraft($draft->fresh(), $this->submitPayloadFromFormState($formState));

            $submittedAt = now();
            $formState['submitted_issue_id'] = $submitted->id;
            $formState['submitted_at'] = $submittedAt->toIso8601String();

            $chatSource->update([
                'form_state' => $formState,
                'draft_issue_id' => null,
            ]);

            $audit->update([
                'submitted_issue_id' => $submitted->id,
                'status' => LineImsSubmission::STATUS_SUCCESS,
                'submitted_at' => $submittedAt,
                'form_state' => $formState,
            ]);

            $this->notifyIssueSubmittedToGroup($chatSource, $submitted, $replyToken);
        } catch (ValidationException $exception) {
            $error = collect($exception->errors())->flatten()->first() ?? $exception->getMessage();

            $audit->update([
                'status' => LineImsSubmission::STATUS_FAILED,
                'error_message' => $error,
            ]);

            $this->messagingClient->pushText($chatSource->source_id, "ส่งไม่สำเร็จ: {$error}");
        } catch (\Throwable $exception) {
            Log::error('LINE IMS submit failed.', [
                'line_chat_source_id' => $chatSource->id,
                'draft_issue_id' => $draft->id,
                'message' => $exception->getMessage(),
            ]);

            $audit->update([
                'status' => LineImsSubmission::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);

            $this->messagingClient->pushText($chatSource->source_id, 'ส่งไม่สำเร็จ: เกิดข้อผิดพลาดภายในระบบ');
        }
    }

    private function resetForm(LineChatSource $chatSource, ?string $replyToken): void
    {
        $formState = LineChatSource::defaultIssueCreateFormState();

        $draft = $this->submissionService->createOrUpdateDraft(
            (string) $chatSource->business_id,
            $this->systemUserId(),
            $this->draftPayloadFromFormState($formState),
        );

        $chatSource->update([
            'form_state' => $formState,
            'draft_issue_id' => $draft->id,
        ]);

        $this->messagingClient->replyText($replyToken, 'เริ่มรับแจ้งปัญหาใหม่แล้ว กรุณาส่งหัวข้อปัญหา');
    }

    /**
     * @param  array<string, mixed>  $formState
     */
    private function replyStatus(?string $replyToken, array $formState): void
    {
        $title = trim((string) ($formState['title'] ?? ''));
        $titleLabel = $title !== '' ? $title : '-';
        $missing = implode(', ', $formState['missing_fields'] ?? []);
        $fileCount = count((array) ($formState['files'] ?? []));

        $message = "บันทึกแล้ว — เรื่อง: {$titleLabel} | ยังขาด: {$missing}";

        if ($fileCount > 0) {
            $message .= " | แนบไฟล์แล้ว ({$fileCount} ไฟล์)";
        }

        $this->messagingClient->replyText($replyToken, $message);
    }

    /**
     * @param  array<string, mixed>  $formState
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>
     */
    private function mergeFormState(array $formState, array $updates): array
    {
        foreach ($updates as $key => $value) {
            $formState[$key] = $value;
        }

        return $formState;
    }

    /**
     * @param  array<string, mixed>  $formState
     */
    private function persistFormState(LineChatSource $chatSource, array $formState): LineChatSource
    {
        $chatSource->update(['form_state' => $formState]);

        return $chatSource->fresh();
    }

    /**
     * @param  array<string, mixed>  $formState
     * @return array<string, mixed>
     */
    private function draftPayloadFromFormState(array $formState): array
    {
        return [
            'title' => $formState['title'] ?? null,
            'comment' => $formState['comment'] ?? '',
            'url' => ($formState['no_url'] ?? false) ? null : ($formState['url'] ?? null),
            'no_url' => (bool) ($formState['no_url'] ?? false),
            'priority' => $formState['priority'] ?? Issue::PRIORITY_MEDIUM,
            'files' => $formState['files'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $formState
     * @return array<string, mixed>
     */
    private function submitPayloadFromFormState(array $formState): array
    {
        $comment = trim((string) ($formState['comment'] ?? ''));

        return [
            'title' => trim((string) ($formState['title'] ?? '')),
            'comment' => $comment !== '' ? $comment : '-',
            'url' => ($formState['no_url'] ?? false) ? null : ($formState['url'] ?? null),
            'priority' => $formState['priority'] ?? Issue::PRIORITY_MEDIUM,
            'files' => $formState['files'] ?? [],
        ];
    }

    private function appendComment(string $existing, string $addition): string
    {
        $addition = trim($addition);

        if ($addition === '') {
            return $existing;
        }

        if ($existing === '') {
            return $addition;
        }

        return $existing."\n".$addition;
    }

    private function shouldAutoSubmit(): bool
    {
        return filter_var(config('services.line.ims.auto_submit'), FILTER_VALIDATE_BOOL);
    }

    private function systemUserId(): int
    {
        return (int) config('services.line.ims.system_user_id');
    }
}
