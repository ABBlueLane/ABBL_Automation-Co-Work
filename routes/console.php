<?php

use App\Services\Monitor\MonitorCheckService;
use App\Services\Monitor\MonitorRetentionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('monitor:run-checks {target?}', function (?string $target = null): void {
    /** @var MonitorCheckService $service */
    $service = app(MonitorCheckService::class);
    $results = $service->runChecks($target);

    if ($results->isEmpty()) {
        $this->warn('No active monitor target found.');

        return;
    }

    $rows = $results->map(fn (array $row): array => [
        $row['target_name'],
        $row['ok'] ? 'UP' : 'FAIL',
        $row['http_status'] ?? '-',
        ($row['latency_ms'] ?? 0).' ms',
        $row['error_message'] ?? '-',
    ])->all();

    $this->table(['Target', 'Result', 'HTTP', 'Latency', 'Error'], $rows);
})->purpose('Run monitor probes and update incidents');

Artisan::command('monitor:purge-checks {--days=}', function (): void {
    $daysOption = $this->option('days');
    $days = $daysOption !== null && $daysOption !== '' ? (int) $daysOption : null;

    /** @var MonitorRetentionService $service */
    $service = app(MonitorRetentionService::class);
    $deleted = $service->purgeExpiredChecks($days);

    $this->info("Purged {$deleted} monitor_checks row(s).");
})->purpose('Delete old monitor_checks beyond retention window');

Schedule::command('monitor:run-checks')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('monitor:purge-checks')
    ->dailyAt('03:15')
    ->withoutOverlapping();
