<?php

namespace App\Observers;

use App\Jobs\ProcessImsCursorAutofix;
use App\Models\Issue;
use App\Services\Cursor\ImsCursorAutofixService;
use Illuminate\Support\Facades\DB;

class IssueObserver
{
    public function __construct(
        private readonly ImsCursorAutofixService $autofixService,
    ) {}

    public function created(Issue $issue): void
    {
        if ($issue->status === Issue::STATUS_PENDING) {
            $this->dispatchAutofix($issue);
        }
    }

    public function updated(Issue $issue): void
    {
        if ($issue->wasChanged('status') && $issue->status === Issue::STATUS_PENDING) {
            $this->dispatchAutofix($issue);
        }
    }

    private function dispatchAutofix(Issue $issue): void
    {
        if (! $this->autofixService->isEnabled()) {
            return;
        }

        $callback = function () use ($issue): void {
            $fresh = $issue->fresh();
            if ($fresh === null || $fresh->status !== Issue::STATUS_PENDING) {
                return;
            }

            $run = $this->autofixService->queueForIssue($fresh);
            if ($run === null || $run->status === \App\Models\CursorAutofixRun::STATUS_SKIPPED) {
                return;
            }

            ProcessImsCursorAutofix::dispatch($run->id)->afterCommit();
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);

            return;
        }

        $callback();
    }
}
