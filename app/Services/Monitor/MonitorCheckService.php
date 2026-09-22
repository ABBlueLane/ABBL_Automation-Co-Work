<?php

namespace App\Services\Monitor;

use App\Models\MonitorCheck;
use App\Models\MonitorIncident;
use App\Models\MonitorTarget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonitorCheckService
{
    public function syncTargetsFromConfig(): void
    {
        $targets = config('monitor.targets', []);

        foreach ($targets as $target) {
            MonitorTarget::firstOrCreate(
                ['name' => $target['name']],
                [
                    'url' => $target['url'],
                    'method' => strtoupper((string) ($target['method'] ?? 'GET')),
                    'interval_seconds' => (int) ($target['interval_seconds'] ?? 60),
                    'timeout_seconds' => (int) ($target['timeout_seconds'] ?? 10),
                    'failure_threshold' => max(1, (int) ($target['failure_threshold'] ?? 2)),
                    'success_threshold' => max(1, (int) ($target['success_threshold'] ?? 1)),
                    'expected_status' => array_values($target['expected_status'] ?? [200]),
                    'is_active' => (bool) ($target['is_active'] ?? true),
                ],
            );
        }
    }

    public function runChecks(?string $targetFilter = null): Collection
    {
        $this->syncTargetsFromConfig();

        $query = MonitorTarget::query()->where('is_active', true);

        if ($targetFilter !== null && $targetFilter !== '') {
            $query->where(function ($q) use ($targetFilter): void {
                $q->where('name', $targetFilter);
                if (ctype_digit($targetFilter)) {
                    $q->orWhere('id', (int) $targetFilter);
                }
            });
        }

        $results = collect();

        foreach ($query->orderBy('id')->get() as $target) {
            $results->push($this->runSingleCheck($target));
        }

        return $results;
    }

    public function runSingleCheck(MonitorTarget $target): array
    {
        $checkedAt = now();
        $startedAt = microtime(true);

        $ok = false;
        $httpStatus = null;
        $errorMessage = null;

        try {
            $response = Http::timeout($target->timeout_seconds)
                ->withHeaders([
                    'User-Agent' => 'ABBL-Uptime-Monitor/1.0',
                ])
                ->send(strtoupper($target->method), $target->url);

            $httpStatus = $response->status();
            $expectedStatuses = $target->expected_status ?: [200];
            $ok = in_array($httpStatus, $expectedStatuses, true);

            if (! $ok) {
                $errorMessage = 'Unexpected HTTP status: '.$httpStatus;
            }
        } catch (Throwable $e) {
            $errorMessage = mb_substr($e->getMessage(), 0, 255);
        }

        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $check = DB::transaction(function () use ($target, $checkedAt, $ok, $httpStatus, $latencyMs, $errorMessage): MonitorCheck {
            $check = MonitorCheck::create([
                'target_id' => $target->id,
                'checked_at' => $checkedAt,
                'ok' => $ok,
                'http_status' => $httpStatus,
                'latency_ms' => $latencyMs,
                'error_message' => $errorMessage,
                'probe_host' => gethostname() ?: php_uname('n'),
            ]);

            $openIncident = MonitorIncident::query()
                ->where('target_id', $target->id)
                ->where('status', MonitorIncident::STATUS_OPEN)
                ->latest('started_at')
                ->first();

            if ($ok) {
                $this->handleSuccessCheck($target, $check, $openIncident);
            } else {
                $this->handleFailedCheck($target, $check, $openIncident);
            }

            return $check;
        });

        return [
            'target_id' => $target->id,
            'target_name' => $target->name,
            'url' => $target->url,
            'ok' => $ok,
            'http_status' => $httpStatus,
            'latency_ms' => $latencyMs,
            'error_message' => $errorMessage,
            'checked_at' => $checkedAt->toIso8601String(),
        ];
    }

    private function handleFailedCheck(MonitorTarget $target, MonitorCheck $check, ?MonitorIncident $openIncident): void
    {
        $target->last_checked_at = $check->checked_at;

        if ($openIncident) {
            $openIncident->checks_failed_count += 1;
            $openIncident->save();
            $target->last_status = 'down';
            $target->save();

            return;
        }

        $consecutiveFailures = $this->countConsecutiveChecks($target->id, false, max(10, $target->failure_threshold + 3));

        if ($consecutiveFailures >= $target->failure_threshold) {
            MonitorIncident::create([
                'target_id' => $target->id,
                'started_at' => $check->checked_at,
                'status' => MonitorIncident::STATUS_OPEN,
                'trigger_http_status' => $check->http_status,
                'trigger_error' => $check->error_message,
                'checks_failed_count' => $consecutiveFailures,
                'notified_down_at' => now(),
            ]);

            Log::warning('Monitor target is DOWN', [
                'target' => $target->name,
                'url' => $target->url,
                'http_status' => $check->http_status,
                'error' => $check->error_message,
                'checked_at' => $check->checked_at?->toIso8601String(),
            ]);

            $target->last_status = 'down';
        } else {
            $target->last_status = 'degraded';
        }

        $target->save();
    }

    private function handleSuccessCheck(MonitorTarget $target, MonitorCheck $check, ?MonitorIncident $openIncident): void
    {
        $target->last_checked_at = $check->checked_at;
        $target->last_status = 'up';

        if (! $openIncident) {
            $target->save();

            return;
        }

        $consecutiveSuccesses = $this->countConsecutiveChecks($target->id, true, max(10, $target->success_threshold + 3));

        if ($consecutiveSuccesses < $target->success_threshold) {
            $target->last_status = 'down';
            $target->save();

            return;
        }

        $endedAt = $check->checked_at;
        $durationSeconds = max(0, $openIncident->started_at->diffInSeconds($endedAt));

        $openIncident->ended_at = $endedAt;
        $openIncident->duration_seconds = $durationSeconds;
        $openIncident->status = MonitorIncident::STATUS_RESOLVED;
        $openIncident->notified_recovered_at = now();
        $openIncident->save();

        Log::notice('Monitor target has recovered', [
            'target' => $target->name,
            'url' => $target->url,
            'duration_seconds' => $durationSeconds,
            'started_at' => $openIncident->started_at?->toIso8601String(),
            'ended_at' => $endedAt?->toIso8601String(),
        ]);

        $target->last_status = 'up';
        $target->save();
    }

    private function countConsecutiveChecks(int $targetId, bool $expectOk, int $limit): int
    {
        $checks = MonitorCheck::query()
            ->where('target_id', $targetId)
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['ok']);

        $count = 0;

        foreach ($checks as $check) {
            if ((bool) $check->ok !== $expectOk) {
                break;
            }

            $count++;
        }

        return $count;
    }
}
