<?php

namespace App\Services\Line\Ims;

use App\Models\Business;
use App\Models\Issue;
use App\Models\User;
use App\Models\LineChatMessage;
use App\Models\LineChatSource;
use App\Models\LineImsSubmission;
use App\Services\Line\LineMessagingClient;
use Illuminate\Support\Facades\DB;
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

        $formState = $chatSource->form_state ?? LineChatSource::defaultIssueCreateFormState();

        if ($this->hasSubmittedInCurrentSession($formState)) {
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
                $this->notifyGroup(
                    $chatSource,
                    'กรุณา @OA แล้วพิมพ์ ยืนยัน เพื่อส่งเข้าระบบ',
                    $replyToken,
                );

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
            $this->notifyGroup($chatSource, 'ไม่รองรับสติกเกอร์เป็นข้อมูลแจ้งปัญหา กรุณาส่งข้อความหรือรูปภาพ', $replyToken);

            return;
        } else {
            return;
        }

        $formState = $this->completer->applyMissingFields($formState);
        $chatSource = $this->persistFormState($chatSource, $formState);
        $this->syncDraftIssue($chatSource, $formState);
        $this->replyStatus($chatSource, $formState, $replyToken);
    }

    public function initializeForm(LineChatSource $chatSource): LineChatSource
    {
        $businessId = $this->resolvedBusinessId();
        $formState = LineChatSource::defaultIssueCreateFormState();

        $chatSource->update([
            'business_id' => $businessId,
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
            'form_state' => $formState,
            'draft_issue_id' => null,
        ]);

        $draft = $this->submissionService->createOrUpdateDraft(
            $businessId,
            $this->systemUserId(),
            $this->draftPayloadFromFormState($formState),
        );

        $chatSource->update(['draft_issue_id' => $draft->id]);

        return $chatSource->fresh();
    }

    /**
     * Submit pending IMS draft when user stops collecting, if the form is complete.
     */
    public function finalizeOnStop(LineChatSource $chatSource, ?string $replyToken, ?string $webhookEventId): ?Issue
    {
        if ($chatSource->form_type !== null && $chatSource->form_type !== LineChatSource::FORM_TYPE_ISSUE_CREATE) {
            return null;
        }

        $formState = $chatSource->form_state ?? LineChatSource::defaultIssueCreateFormState();

        if ($this->hasSubmittedInCurrentSession($formState)) {
            $issueId = (int) ($formState['submitted_issue_id'] ?? 0);

            return $issueId > 0 ? Issue::query()->find($issueId) : null;
        }

        $chatSource = $this->ensureChatSourceConfigured($chatSource);
        $formState = $this->prepareFormStateForStopSubmit(
            $chatSource->form_state ?? LineChatSource::defaultIssueCreateFormState(),
        );

        if (! $this->completer->isComplete($formState)) {
            return null;
        }

        $chatSource->update(['form_state' => $formState]);
        $this->syncDraftIssue($chatSource->fresh(), $formState);

        return $this->attemptSubmit($chatSource->fresh(), $formState, $replyToken, $webhookEventId, notifyOnSuccess: true);
    }

    /**
     * @param  array<string, mixed>  $formState
     * @return array<string, mixed>
     */
    private function prepareFormStateForStopSubmit(array $formState): array
    {
        $title = trim((string) ($formState['title'] ?? ''));
        $comment = trim((string) ($formState['comment'] ?? ''));

        if (in_array(mb_strtolower($title), ['สร้าง', 'ไม่สร้าง'], true) && $comment !== '') {
            $lines = preg_split("/\r\n|\n|\r/", $comment, 2) ?: [$comment];
            $formState['title'] = mb_substr(trim((string) ($lines[0] ?? $comment)), 0, 255);
            $formState['comment'] = trim((string) ($lines[1] ?? ''));
        }

        $missing = $this->completer->missingFields($formState);

        if ($missing === ['url_or_no_url']) {
            $formState['no_url'] = true;
            $formState['url'] = null;
        }

        return $this->completer->applyMissingFields($formState);
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

        return "{$baseUrl}/issue/view/{$issueId}";
    }

    private function notifyIssueSubmittedToGroup(LineChatSource $chatSource, Issue $issue, ?string $replyToken): void
    {
        $message = $this->successMessage($issue, (string) $chatSource->business_id);
        $this->notifyGroup($chatSource, $message, $replyToken);
    }

    private function notifyGroup(LineChatSource $chatSource, string $text, ?string $replyToken = null): void
    {
        $destination = trim((string) $chatSource->source_id);

        if ($destination === '') {
            Log::warning('LINE IMS notify skipped: missing group id.', [
                'line_chat_source_id' => $chatSource->id,
            ]);
            $this->messagingClient->replyText($replyToken, $text);

            return;
        }

        $this->messagingClient->notifyChat($destination, $text, $replyToken);
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

        if ($chatSource->draft_issue_id === null && ! $this->hasSubmittedInCurrentSession($chatSource->form_state ?? [])) {
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
        if ($this->hasSubmittedInCurrentSession($formState)) {
            return;
        }

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
        bool $notifyOnSuccess = false,
    ): ?Issue {
        if (! $this->completer->isComplete($formState)) {
            $missing = implode(', ', $formState['missing_fields'] ?? $this->completer->missingFields($formState));
            $this->notifyGroup($chatSource, "ยังส่งไม่ได้ — ข้อมูลยังไม่ครบ: {$missing}", $replyToken);

            return null;
        }

        if ($webhookEventId !== null && $webhookEventId !== '') {
            $existingSubmission = LineImsSubmission::query()
                ->where('webhook_event_id', $webhookEventId)
                ->exists();

            if ($existingSubmission) {
                return null;
            }
        }

        $submitted = null;

        try {
            $submitted = DB::transaction(function () use ($chatSource, $formState, $webhookEventId) {
                $lockedSource = LineChatSource::query()
                    ->lockForUpdate()
                    ->find($chatSource->id);

                if ($lockedSource === null) {
                    return null;
                }

                $lockedState = $lockedSource->form_state ?? [];

                if ($this->hasSubmittedInCurrentSession($lockedState)) {
                    return 'already_submitted';
                }

                $draft = $lockedSource->draftIssue;

                if ($draft === null || $draft->status !== Issue::STATUS_DRAFT) {
                    return null;
                }

                $audit = LineImsSubmission::query()->create([
                    'line_chat_source_id' => $lockedSource->id,
                    'draft_issue_id' => $draft->id,
                    'webhook_event_id' => $webhookEventId,
                    'status' => LineImsSubmission::STATUS_PENDING,
                    'form_state' => $formState,
                ]);

                $this->syncDraftIssue($lockedSource, $formState);

                $issue = $this->submissionService->submitDraft(
                    $draft->fresh(),
                    $this->submitPayloadFromFormState($formState),
                );

                $submittedAt = now();
                $lockedState['submitted_issue_id'] = $issue->id;
                $lockedState['submitted_at'] = $submittedAt->toIso8601String();

                $lockedSource->update([
                    'form_state' => $lockedState,
                    'draft_issue_id' => null,
                ]);

                $audit->update([
                    'submitted_issue_id' => $issue->id,
                    'status' => LineImsSubmission::STATUS_SUCCESS,
                    'submitted_at' => $submittedAt,
                    'form_state' => $lockedState,
                ]);

                return $issue;
            });
        } catch (ValidationException $exception) {
            $error = collect($exception->errors())->flatten()->first() ?? $exception->getMessage();

            LineImsSubmission::query()
                ->where('line_chat_source_id', $chatSource->id)
                ->where('webhook_event_id', $webhookEventId)
                ->where('status', LineImsSubmission::STATUS_PENDING)
                ->update([
                    'status' => LineImsSubmission::STATUS_FAILED,
                    'error_message' => $error,
                ]);

            $this->messagingClient->notifyChat(
                (string) $chatSource->source_id,
                "ส่งไม่สำเร็จ: {$error}",
                $replyToken,
            );

            return null;
        } catch (\Throwable $exception) {
            Log::error('LINE IMS submit failed.', [
                'line_chat_source_id' => $chatSource->id,
                'message' => $exception->getMessage(),
            ]);

            LineImsSubmission::query()
                ->where('line_chat_source_id', $chatSource->id)
                ->where('webhook_event_id', $webhookEventId)
                ->where('status', LineImsSubmission::STATUS_PENDING)
                ->update([
                    'status' => LineImsSubmission::STATUS_FAILED,
                    'error_message' => $exception->getMessage(),
                ]);

            $this->messagingClient->notifyChat(
                (string) $chatSource->source_id,
                'ส่งไม่สำเร็จ: เกิดข้อผิดพลาดภายในระบบ',
                $replyToken,
            );

            return null;
        }

        if ($submitted === 'already_submitted') {
            $issueId = (int) (($chatSource->fresh()->form_state ?? [])['submitted_issue_id'] ?? 0);

            return $issueId > 0 ? Issue::query()->find($issueId) : null;
        }

        if (! $submitted instanceof Issue) {
            $this->notifyGroup($chatSource, 'ไม่พบแบบร่างสำหรับส่งเข้าระบบ', $replyToken);

            return null;
        }

        if ($notifyOnSuccess) {
            $this->notifyIssueSubmittedToGroup($chatSource, $submitted, $replyToken);
        }

        return $submitted;
    }

    /**
     * @param  array<string, mixed>  $formState
     */
    private function hasSubmittedInCurrentSession(array $formState): bool
    {
        return ! empty($formState['submitted_issue_id']);
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

        $this->notifyGroup($chatSource, 'เริ่มรับแจ้งปัญหาใหม่แล้ว กรุณาส่งหัวข้อปัญหา', $replyToken);
    }

    /**
     * @param  array<string, mixed>  $formState
     */
    private function replyStatus(LineChatSource $chatSource, array $formState, ?string $replyToken): void
    {
        $this->notifyGroup($chatSource, 'บันทึกข้อความล่าสุดแล้ว', $replyToken);
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

    private function systemUserId(): int
    {
        $this->assertImsPrerequisites();

        return (int) config('services.line.ims.system_user_id');
    }

    private function resolvedBusinessId(): string
    {
        $this->assertImsPrerequisites();

        return trim((string) config('services.line.ims.default_business_id'));
    }

    private function assertImsPrerequisites(): void
    {
        $businessId = trim((string) config('services.line.ims.default_business_id'));
        $systemUserId = (int) config('services.line.ims.system_user_id');

        if ($businessId === '' || $systemUserId <= 0) {
            throw new \RuntimeException('LINE IMS is not configured (missing business id or system user id).');
        }

        if (! Business::query()->whereKey($businessId)->exists()) {
            throw new \RuntimeException("LINE IMS business not found: {$businessId}");
        }

        if (! User::query()->whereKey($systemUserId)->exists()) {
            throw new \RuntimeException("LINE IMS system user not found: {$systemUserId}");
        }
    }
}
