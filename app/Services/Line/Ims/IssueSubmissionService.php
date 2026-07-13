<?php

namespace App\Services\Line\Ims;

use App\Models\Business;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\IssueProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IssueSubmissionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrUpdateDraft(string $businessId, int $userId, array $data, ?Issue $existingDraft = null): Issue
    {
        Business::findOrFail($businessId);

        return DB::transaction(function () use ($businessId, $userId, $data, $existingDraft) {
            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                $title = 'แบบร่าง';
            }

            $priority = $this->resolvePriority($data['priority'] ?? null);
            $url = ($data['no_url'] ?? false) ? null : ($data['url'] ?? null);
            $comment = (string) ($data['comment'] ?? '');
            $files = (array) ($data['files'] ?? []);
            $issueProjectId = isset($data['issue_project_id']) ? (int) $data['issue_project_id'] : null;

            if ($existingDraft !== null) {
                $existingDraft->update([
                    'title' => $title,
                    'url' => $url,
                    'issue_project_id' => $issueProjectId,
                    'priority' => $priority,
                ]);

                $this->syncFirstComment($existingDraft, $userId, $comment, $files);

                return $existingDraft->fresh();
            }

            $draft = Issue::create([
                'business_id' => $businessId,
                'issue_project_id' => $issueProjectId,
                'issue_number' => Issue::generateDraftIssueNumber(),
                'title' => $title,
                'url' => $url,
                'status' => Issue::STATUS_DRAFT,
                'priority' => $priority,
                'created_by' => $userId,
                'assigned_to' => null,
            ]);

            IssueComment::create([
                'issue_id' => $draft->id,
                'user_id' => $userId,
                'comment' => $comment,
                'files' => $files,
            ]);

            return $draft;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitDraft(Issue $issue, array $data): Issue
    {
        if ($issue->status !== Issue::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'issue' => ['รายการนี้ไม่ใช่แบบร่าง'],
            ]);
        }

        $validated = $this->validateSubmitData($data);

        return DB::transaction(function () use ($issue, $validated) {
            $issueProject = isset($validated['issue_project_id'])
                ? IssueProject::find((int) $validated['issue_project_id'])
                : null;

            $issue->update([
                'issue_project_id' => $issueProject?->id,
                'issue_number' => Issue::getNo((string) $issue->business_id),
                'title' => $validated['title'],
                'url' => $validated['url'],
                'status' => Issue::STATUS_PENDING,
                'priority' => $validated['priority'],
                'assigned_to' => $issueProject?->responsible_user_id,
            ]);

            $this->syncFirstComment(
                $issue,
                (int) $issue->created_by,
                $validated['comment'] ?? '',
                $validated['files'] ?? [],
            );

            return $issue->fresh();
        });
    }

    public function submitFromDraft(Issue $issue): Issue
    {
        $issue->loadMissing('firstComment');

        return $this->submitDraft($issue, [
            'title' => $issue->title === 'แบบร่าง' ? '' : $issue->title,
            'comment' => $issue->firstComment?->comment ?? '',
            'url' => $issue->url,
            'files' => $issue->firstComment?->files ?? [],
            'issue_project_id' => $issue->issue_project_id,
            'priority' => $issue->priority,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateSubmitData(array $data): array
    {
        if (($data['url'] ?? null) === '') {
            $data['url'] = null;
        }

        return Validator::make($data, $this->issueSubmitRules())->validate();
    }

    /**
     * @return array<string, mixed>
     */
    public function issueSubmitRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'priority' => ['required', 'in:'.implode(',', array_keys(Issue::getPriorityOptions()))],
            'comment' => 'required|string',
            'url' => 'nullable|url|max:2048',
            'files' => 'nullable|array',
            'files.*' => 'string',
            'issue_project_id' => ['nullable', 'integer', 'exists:issue_projects,id'],
        ];
    }

    private function resolvePriority(mixed $priority): string
    {
        $valid = array_keys(Issue::getPriorityOptions());

        if (is_string($priority) && in_array($priority, $valid, true)) {
            return $priority;
        }

        return Issue::PRIORITY_MEDIUM;
    }

    private function syncFirstComment(Issue $issue, int $userId, string $comment, array $files): void
    {
        $first = $issue->firstComment;

        if ($first) {
            $first->update([
                'comment' => $comment,
                'files' => $files,
            ]);

            return;
        }

        IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => $userId,
            'comment' => $comment,
            'files' => $files,
        ]);
    }
}
