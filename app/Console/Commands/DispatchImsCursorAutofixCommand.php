<?php

namespace App\Console\Commands;

use App\Jobs\ProcessImsCursorAutofix;
use App\Models\CursorAutofixRun;
use App\Models\Issue;
use App\Services\Cursor\ImsCursorAutofixService;
use Illuminate\Console\Command;

class DispatchImsCursorAutofixCommand extends Command
{
    protected $signature = 'ims:cursor-autofix
                            {issue : Issue ID}
                            {--repo= : Override mapped repository key (e.g. AB_Gateway)}
                            {--sync : Run immediately instead of queueing}';

    protected $description = 'Dispatch Cursor CLI autofix for an IMS issue';

    public function handle(ImsCursorAutofixService $service): int
    {
        if (! $service->isEnabled()) {
            $this->error('Cursor autofix is disabled. Set CURSOR_AUTOFIX_ENABLED=true');

            return self::FAILURE;
        }

        $issue = Issue::query()->find($this->argument('issue'));
        if ($issue === null) {
            $this->error('Issue not found.');

            return self::FAILURE;
        }

        $repoKey = $this->option('repo');
        $run = $service->queueForIssue($issue, is_string($repoKey) && $repoKey !== '' ? $repoKey : null);

        if ($run === null) {
            $this->warn('No run created (already queued/running, or issue not pending).');

            return self::FAILURE;
        }

        $this->info("Created autofix run #{$run->id} [{$run->status}] repo=".($run->repo_key ?: '-'));

        if ($run->status === CursorAutofixRun::STATUS_SKIPPED) {
            $this->warn($run->error_message ?: 'Skipped');

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $run = $service->execute($run);
            $this->info("Finished with status {$run->status}");
            if ($run->error_message) {
                $this->error($run->error_message);
            }

            return $run->status === CursorAutofixRun::STATUS_SUCCEEDED
                ? self::SUCCESS
                : self::FAILURE;
        }

        ProcessImsCursorAutofix::dispatch($run->id);
        $this->info('Dispatched to queue.');

        return self::SUCCESS;
    }
}
