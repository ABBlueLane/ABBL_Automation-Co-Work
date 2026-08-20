<?php

namespace App\Services\Cursor;

use App\Models\CursorAutofixRun;
use App\Models\Issue;
use App\Models\IssueComment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class ImsCursorAutofixService
{
    public function __construct(
        private readonly RepoTargetResolver $repoResolver,
        private readonly AutofixPromptBuilder $promptBuilder,
        private readonly CursorCliClient $cliClient,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('cursor.autofix.enabled', false);
    }

    /**
     * Queue a new autofix run for a pending IMS issue when a repo mapping matches.
     */
    public function queueForIssue(Issue $issue, ?string $repoKeyOverride = null): ?CursorAutofixRun
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if ($issue->status !== Issue::STATUS_PENDING) {
            return null;
        }

        $existing = CursorAutofixRun::query()
            ->where('issue_id', $issue->id)
            ->whereIn('status', [
                CursorAutofixRun::STATUS_QUEUED,
                CursorAutofixRun::STATUS_RUNNING,
            ])
            ->exists();

        if ($existing) {
            return null;
        }

        $repo = $repoKeyOverride
            ? $this->repoResolver->findByKey($repoKeyOverride)
            : $this->repoResolver->resolve($issue->url);

        if ($repo === null) {
            Log::info('Cursor autofix skipped: no repo mapping for issue URL.', [
                'issue_id' => $issue->id,
                'url' => $issue->url,
            ]);

            return CursorAutofixRun::create([
                'issue_id' => $issue->id,
                'status' => CursorAutofixRun::STATUS_SKIPPED,
                'error_message' => 'No repository mapping matched the issue URL.',
                'matched_host' => $this->hostFromUrl($issue->url),
                'finished_at' => now(),
            ]);
        }

        $branch = $this->suggestedBranchName($issue);
        $prompt = $this->promptBuilder->build($issue, $repo, $branch);
        $dryRun = (bool) config('cursor.autofix.dry_run', true);
        $invocation = $this->cliClient->buildInvocation($prompt, $repo['path']);

        return CursorAutofixRun::create([
            'issue_id' => $issue->id,
            'status' => CursorAutofixRun::STATUS_QUEUED,
            'repo_key' => $repo['key'],
            'repo_name' => $repo['name'],
            'repo_path' => $repo['path'],
            'matched_host' => $repo['matched_host'] ?? $this->hostFromUrl($issue->url),
            'git_branch' => $branch,
            'prompt' => $prompt,
            'command' => $invocation['argv'],
            'dry_run' => $dryRun,
        ]);
    }

    public function execute(CursorAutofixRun $run): CursorAutofixRun
    {
        $run->refresh();

        if ($run->status === CursorAutofixRun::STATUS_SKIPPED) {
            return $run;
        }

        if (! in_array($run->status, [CursorAutofixRun::STATUS_QUEUED, CursorAutofixRun::STATUS_RUNNING], true)) {
            return $run;
        }

        $run->update([
            'status' => CursorAutofixRun::STATUS_RUNNING,
            'started_at' => $run->started_at ?? now(),
            'error_message' => null,
        ]);

        $repoPath = (string) $run->repo_path;

        if ($repoPath === '' || ! is_dir($repoPath)) {
            return $this->fail($run, "Repository path does not exist: {$repoPath}");
        }

        if ($run->dry_run) {
            $message = "Dry run only — would execute Cursor CLI in {$repoPath} on branch {$run->git_branch}.";
            $run->update([
                'status' => CursorAutofixRun::STATUS_SUCCEEDED,
                'stdout' => $message,
                'exit_code' => 0,
                'finished_at' => now(),
            ]);
            $this->maybePostComment($run->fresh(), $this->successCommentBody($run->fresh()));

            return $run->fresh();
        }

        if (! $this->cliClient->binaryAvailable()) {
            return $this->fail($run, 'Cursor CLI binary not found (configure CURSOR_CLI_BINARY / install agent).');
        }

        if (trim((string) config('cursor.cli.api_key', '')) === '') {
            return $this->fail($run, 'CURSOR_API_KEY is not configured.');
        }

        try {
            if ((bool) config('cursor.autofix.create_branch', true) && $run->git_branch) {
                $branchError = $this->ensureGitBranch($repoPath, (string) $run->git_branch);
                if ($branchError !== null) {
                    return $this->fail($run, $branchError);
                }
            }

            $result = $this->cliClient->run(
                (string) $run->prompt,
                $repoPath,
                (int) config('cursor.autofix.timeout_seconds', 900),
            );

            $succeeded = $result['exit_code'] === 0;
            $run->update([
                'status' => $succeeded ? CursorAutofixRun::STATUS_SUCCEEDED : CursorAutofixRun::STATUS_FAILED,
                'command' => $result['command'],
                'stdout' => $this->truncate((string) $result['stdout']),
                'stderr' => $this->truncate((string) $result['stderr']),
                'exit_code' => $result['exit_code'],
                'error_message' => $succeeded ? null : 'Cursor CLI exited with a non-zero status.',
                'finished_at' => now(),
            ]);

            $summary = $succeeded
                ? $this->successCommentBody($run->fresh())
                : $this->failureCommentBody($run->fresh());

            $this->maybePostComment($run->fresh(), $summary);

            return $run->fresh();
        } catch (\Throwable $exception) {
            Log::error('Cursor autofix failed.', [
                'run_id' => $run->id,
                'issue_id' => $run->issue_id,
                'message' => $exception->getMessage(),
            ]);

            return $this->fail($run, $exception->getMessage());
        }
    }

    private function fail(CursorAutofixRun $run, string $message): CursorAutofixRun
    {
        $run->update([
            'status' => CursorAutofixRun::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => now(),
        ]);

        $this->maybePostComment($run->fresh(), $this->failureCommentBody($run->fresh()));

        return $run->fresh();
    }

    private function ensureGitBranch(string $repoPath, string $branch): ?string
    {
        $gitDir = $repoPath.DIRECTORY_SEPARATOR.'.git';
        if (! is_dir($gitDir) && ! is_file($gitDir)) {
            return 'Repository path is not a git checkout.';
        }

        $current = Process::path($repoPath)->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        if ($current->successful() && trim($current->output()) === $branch) {
            return null;
        }

        $create = Process::path($repoPath)->run(['git', 'checkout', '-B', $branch]);
        if (! $create->successful()) {
            return 'Failed to create/switch git branch: '.trim($create->errorOutput() ?: $create->output());
        }

        return null;
    }

    private function maybePostComment(CursorAutofixRun $run, string $body): void
    {
        if (! (bool) config('cursor.autofix.post_comment', true)) {
            return;
        }

        $userId = config('cursor.autofix.system_user_id');
        if ($userId === null || $userId === '') {
            return;
        }

        IssueComment::create([
            'issue_id' => $run->issue_id,
            'user_id' => (int) $userId,
            'comment' => $body,
            'files' => [],
        ]);
    }

    private function successCommentBody(CursorAutofixRun $run): string
    {
        $lines = [
            '[Cursor Autofix] สำเร็จ',
            'Repo: '.($run->repo_name ?? $run->repo_key),
            'Branch: '.($run->git_branch ?: '-'),
            'Mode: '.($run->dry_run ? 'dry-run' : 'live'),
        ];

        $stdout = trim((string) $run->stdout);
        if ($stdout !== '') {
            $lines[] = '';
            $lines[] = Str::limit($stdout, 3500);
        }

        return implode("\n", $lines);
    }

    private function failureCommentBody(CursorAutofixRun $run): string
    {
        return implode("\n", array_filter([
            '[Cursor Autofix] ไม่สำเร็จ',
            'Repo: '.($run->repo_name ?? $run->repo_key ?? '-'),
            'Branch: '.($run->git_branch ?: '-'),
            'Error: '.($run->error_message ?: 'unknown'),
            $run->stderr ? Str::limit(trim((string) $run->stderr), 1500) : null,
        ]));
    }

    private function suggestedBranchName(Issue $issue): string
    {
        $slug = Str::slug((string) $issue->issue_number, '-');
        if ($slug === '') {
            $slug = (string) $issue->id;
        }

        return 'cursor/ims-'.$slug.'-autofix';
    }

    private function hostFromUrl(?string $url): ?string
    {
        $host = parse_url((string) $url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? mb_strtolower($host) : null;
    }

    private function truncate(string $value, int $limit = 200000): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit)."\n...[truncated]";
    }
}
