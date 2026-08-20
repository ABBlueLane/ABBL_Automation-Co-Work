<?php

namespace App\Jobs;

use App\Models\CursorAutofixRun;
use App\Services\Cursor\ImsCursorAutofixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImsCursorAutofix implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 1200;

    public function __construct(
        public int $runId,
    ) {}

    public function handle(ImsCursorAutofixService $service): void
    {
        $run = CursorAutofixRun::query()->find($this->runId);
        if ($run === null) {
            return;
        }

        $service->execute($run);
    }
}
