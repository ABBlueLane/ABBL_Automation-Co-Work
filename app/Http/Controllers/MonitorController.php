<?php

namespace App\Http\Controllers;

use App\Models\MonitorCheck;
use App\Models\MonitorIncident;
use App\Models\MonitorTarget;
use App\Services\Monitor\MonitorAlertService;
use App\Services\Monitor\MonitorCheckService;
use App\Services\Monitor\MonitorSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function settings(MonitorSettingsService $settings): View
    {
        $lineGroups = $settings->availableLineGroups(refreshNames: true);

        return view('monitor.settings', [
            'displayTimezone' => config('monitor.timezone_display', 'Asia/Bangkok'),
            'alertsEnabled' => $settings->alertsEnabled(),
            'selectedLineSourceId' => $settings->lineGroupSourceId(),
            'lineGroups' => $lineGroups,
            'lineTokenConfigured' => $settings->lineTokenConfigured(),
            'selectedGroup' => $settings->selectedLineGroup(),
            'botDisplayName' => $settings->botDisplayName(),
            'statusMessageSuffix' => $settings->statusMessageSuffix(),
        ]);
    }

    public function refreshLineGroups(MonitorSettingsService $settings): RedirectResponse
    {
        $updated = $settings->refreshGroupDisplayNames(force: true);

        return redirect()
            ->route('monitor.settings')
            ->with('success', $updated > 0
                ? "อัปเดตชื่อกลุ่มแล้ว {$updated} รายการ"
                : 'ยังอัปเดตชื่อกลุ่มไม่ได้ — ตรวจ token หรือว่า OA ยังอยู่ในกลุ่ม');
    }

    public function updateSettings(Request $request, MonitorSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'alerts_enabled' => ['nullable', 'boolean'],
            'line_chat_source_id' => ['nullable', 'string', 'max:255'],
            'status_message_suffix' => ['nullable', 'string', 'max:1000'],
        ]);

        $sourceId = $validated['line_chat_source_id'] ?? null;
        if (is_string($sourceId) && $sourceId !== '') {
            $exists = $settings->availableLineGroups()->contains(
                fn ($group): bool => $group->source_id === $sourceId
            );

            if (! $exists) {
                return back()->withErrors([
                    'line_chat_source_id' => 'กลุ่มที่เลือกยังไม่อยู่ในรายการ — เชิญ OA เข้ากลุ่มแล้วให้มีข้อความเข้ามาก่อน',
                ])->withInput();
            }
        }

        $settings->save([
            'alerts_enabled' => $request->boolean('alerts_enabled'),
            'line_chat_source_id' => $sourceId ?: null,
            'status_message_suffix' => $validated['status_message_suffix'] ?? null,
        ]);

        return redirect()
            ->route('monitor.settings')
            ->with('success', 'บันทึกการตั้งค่าแจ้งเตือนแล้ว');
    }

    public function sendTestAlert(Request $request, MonitorAlertService $alertService, MonitorSettingsService $settings): RedirectResponse
    {
        $sourceId = $request->input('line_chat_source_id') ?: $settings->lineGroupSourceId();
        $result = $alertService->sendTestMessage(is_string($sourceId) ? $sourceId : null);

        $redirectTo = $request->headers->get('referer') && str_contains((string) $request->headers->get('referer'), '/monitor/settings')
            ? route('monitor.settings')
            : route('monitor.index');

        return redirect()
            ->to($redirectTo)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function index(): View
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $baseline = $this->resolveBaseline($timezone);

        return view('monitor.index', [
            'displayTimezone' => $timezone,
            'baselineStartedAt' => $baseline?->setTimezone($timezone)->format('Y-m-d H:i'),
            'checksRetentionDays' => (int) config('monitor.checks_retention_days', 90),
        ]);
    }

    public function status(): JsonResponse
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $latencyThreshold = (int) config('monitor.latency_threshold_ms', 10000);
        $targets = MonitorTarget::query()
            ->orderBy('id')
            ->get();

        $latestCheckedAt = null;

        $rows = $targets->map(function (MonitorTarget $target) use ($timezone, $latencyThreshold, &$latestCheckedAt): array {
            $latestCheck = MonitorCheck::query()
                ->where('target_id', $target->id)
                ->orderByDesc('checked_at')
                ->orderByDesc('id')
                ->first();

            $openIncident = MonitorIncident::query()
                ->where('target_id', $target->id)
                ->where('status', MonitorIncident::STATUS_OPEN)
                ->latest('started_at')
                ->first();

            if ($latestCheck?->checked_at && ($latestCheckedAt === null || $latestCheck->checked_at->gt($latestCheckedAt))) {
                $latestCheckedAt = $latestCheck->checked_at;
            }

            $status = 'unknown';
            if ($openIncident) {
                $status = 'down';
            } elseif (! $target->is_active) {
                $status = 'inactive';
            } elseif ($latestCheck) {
                if (! $latestCheck->ok) {
                    $status = 'degraded';
                } elseif ($latencyThreshold > 0 && (int) $latestCheck->latency_ms >= $latencyThreshold) {
                    $status = 'degraded';
                } else {
                    $status = 'up';
                }
            }

            return [
                'id' => $target->id,
                'name' => $target->name,
                'url' => $target->url,
                'is_active' => (bool) $target->is_active,
                'method' => $target->method,
                'interval_seconds' => (int) $target->interval_seconds,
                'timeout_seconds' => (int) $target->timeout_seconds,
                'failure_threshold' => (int) $target->failure_threshold,
                'success_threshold' => (int) $target->success_threshold,
                'expected_status' => $target->expected_status ?: [200],
                'status' => $status,
                'http_status' => $latestCheck?->http_status,
                'latency_ms' => $latestCheck?->latency_ms,
                'error_message' => $latestCheck?->error_message,
                'checked_at' => $latestCheck?->checked_at?->setTimezone($timezone)->format('Y-m-d H:i:s'),
                'checked_at_iso' => $latestCheck?->checked_at?->toIso8601String(),
                'open_incident_id' => $openIncident?->id,
            ];
        });

        $totals = $this->buildTotals($timezone);
        $baseline = $this->resolveBaseline($timezone);

        return response()->json([
            'checked_at' => $latestCheckedAt?->toIso8601String() ?? now()->toIso8601String(),
            'timezone_display' => $timezone,
            'baseline_started_at' => $baseline?->toIso8601String(),
            'targets' => $rows,
            'totals' => $totals,
            'note' => ($totals['incidents_7d'] === 0 && $latestCheckedAt === null)
                ? 'totals are 0 until history exists'
                : null,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $range = $request->string('range')->toString() ?: '7d';
        [$fromUtc, $toUtc] = $this->resolveRangeUtc($range, $timezone);

        $totals = $this->buildTotals($timezone);
        $periodSeconds = max(1, $fromUtc->diffInSeconds($toUtc));
        $downtimeInRange = $this->sumDowntimeSeconds($fromUtc, $toUtc);
        $uptimePercent = round(max(0, min(100, (1 - ($downtimeInRange / $periodSeconds)) * 100)), 2);

        $latencyStats = MonitorCheck::query()
            ->where('checked_at', '>=', $fromUtc)
            ->where('checked_at', '<=', $toUtc)
            ->whereNotNull('latency_ms')
            ->orderBy('latency_ms')
            ->pluck('latency_ms');

        return response()->json([
            'timezone_display' => $timezone,
            'range' => $range,
            'totals' => $totals,
            'uptime_percent' => $uptimePercent,
            'latency' => [
                'p50' => $this->percentile($latencyStats->all(), 50),
                'p95' => $this->percentile($latencyStats->all(), 95),
                'samples' => $latencyStats->count(),
            ],
        ]);
    }

    public function incidents(Request $request): JsonResponse
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $fromDate = $request->date('from');
        $toDate = $request->date('to');

        $query = MonitorIncident::query()
            ->with('target')
            ->orderByDesc('started_at');

        if ($request->filled('target_id')) {
            $query->where('target_id', (int) $request->input('target_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($fromDate) {
            $query->where('started_at', '>=', CarbonImmutable::parse($fromDate, $timezone)->startOfDay()->utc());
        }

        if ($toDate) {
            $query->where('started_at', '<=', CarbonImmutable::parse($toDate, $timezone)->endOfDay()->utc());
        }

        $incidents = $query->paginate(20);

        $rows = collect($incidents->items())->map(function (MonitorIncident $incident) use ($timezone): array {
            $isOpen = $incident->status === MonitorIncident::STATUS_OPEN;
            $durationSeconds = $incident->duration_seconds
                ?? ($incident->started_at ? max(0, $incident->started_at->diffInSeconds(now())) : 0);

            return [
                'id' => $incident->id,
                'target_id' => $incident->target_id,
                'target' => $incident->target?->name,
                'status' => $incident->status,
                'started_at' => $incident->started_at?->setTimezone($timezone)->format('Y-m-d H:i:s'),
                'ended_at' => $incident->ended_at?->setTimezone($timezone)->format('Y-m-d H:i:s'),
                'duration_seconds' => $durationSeconds,
                'trigger_http_status' => $incident->trigger_http_status,
                'trigger_error' => $incident->trigger_error,
                'is_open' => $isOpen,
            ];
        });

        return response()->json([
            'timezone_display' => $timezone,
            'items' => $rows,
            'pagination' => [
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'total' => $incidents->total(),
            ],
        ]);
    }

    public function byHour(Request $request): JsonResponse
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        [$fromUtc, $toUtc] = $this->resolveRangeUtc($request->string('range')->toString(), $timezone);

        $incidents = MonitorIncident::query()
            ->where('started_at', '>=', $fromUtc)
            ->where('started_at', '<=', $toUtc)
            ->get(['started_at']);

        $hours = array_fill(0, 24, 0);

        foreach ($incidents as $incident) {
            if (! $incident->started_at) {
                continue;
            }

            $hour = (int) $incident->started_at->setTimezone($timezone)->format('G');
            $hours[$hour]++;
        }

        $series = collect($hours)->map(fn (int $count, int $hour): array => [
            'hour' => $hour,
            'count' => $count,
        ])->values();

        $topHours = $series->sortByDesc('count')->take(5)->values();

        return response()->json([
            'range' => $request->string('range')->toString() ?: '30d',
            'timezone_display' => $timezone,
            'hours' => $series,
            'top_hours' => $topHours,
        ]);
    }

    public function runChecks(Request $request, MonitorCheckService $checkService): JsonResponse
    {
        $target = $request->string('target')->toString();
        $results = $checkService->runChecks($target !== '' ? $target : null);

        return response()->json([
            'ok' => true,
            'ran' => $results->count(),
            'results' => $results,
        ]);
    }

    public function runChecksInternal(Request $request, MonitorCheckService $checkService): JsonResponse
    {
        $configuredSecret = (string) config('monitor.internal_run_secret', '');
        $providedSecret = (string) $request->header('X-Monitor-Secret', $request->input('secret', ''));

        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $providedSecret)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $target = $request->string('target')->toString();
        $results = $checkService->runChecks($target !== '' ? $target : null);

        return response()->json([
            'ok' => true,
            'ran' => $results->count(),
            'results' => $results,
        ]);
    }

    public function updateTarget(Request $request, MonitorTarget $target, MonitorCheckService $checkService): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
            'method' => ['required', Rule::in(['GET', 'HEAD'])],
            'interval_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:120'],
            'failure_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'success_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'expected_status' => ['required', 'array', 'min:1'],
            'expected_status.*' => ['integer', 'between:100,599'],
            'is_active' => ['required', 'boolean'],
        ]);

        $target->fill([
            'url' => $validated['url'],
            'method' => $validated['method'],
            'interval_seconds' => $validated['interval_seconds'],
            'timeout_seconds' => $validated['timeout_seconds'],
            'failure_threshold' => $validated['failure_threshold'],
            'success_threshold' => $validated['success_threshold'],
            'expected_status' => array_values(array_unique($validated['expected_status'])),
            'is_active' => $validated['is_active'],
        ]);
        $target->save();

        $checkResult = null;
        if ($target->is_active) {
            $checkResult = $checkService->runSingleCheck($target->fresh());
        }

        return response()->json([
            'ok' => true,
            'message' => $target->is_active ? 'Target updated and check executed.' : 'Target updated.',
            'check_result' => $checkResult,
        ]);
    }

    /**
     * @return array{incidents_today: int, incidents_7d: int, incidents_month: int, downtime_seconds_7d: int}
     */
    private function buildTotals(string $timezone): array
    {
        $nowInTimezone = CarbonImmutable::now($timezone);

        $todayStartUtc = $nowInTimezone->startOfDay()->utc();
        $todayEndUtc = $nowInTimezone->endOfDay()->utc();
        $monthStartUtc = $nowInTimezone->startOfMonth()->utc();
        $monthEndUtc = $nowInTimezone->endOfMonth()->utc();
        $sevenDaysAgoUtc = $nowInTimezone->subDays(7)->utc();

        return [
            'incidents_today' => MonitorIncident::query()
                ->where('started_at', '>=', $todayStartUtc)
                ->where('started_at', '<=', $todayEndUtc)
                ->count(),
            'incidents_7d' => MonitorIncident::query()
                ->where('started_at', '>=', $sevenDaysAgoUtc)
                ->count(),
            'incidents_month' => MonitorIncident::query()
                ->where('started_at', '>=', $monthStartUtc)
                ->where('started_at', '<=', $monthEndUtc)
                ->count(),
            'downtime_seconds_7d' => $this->sumDowntimeSeconds($sevenDaysAgoUtc, now()->toImmutable()),
        ];
    }

    private function sumDowntimeSeconds(CarbonImmutable $fromUtc, CarbonImmutable|\Carbon\CarbonInterface $toUtc): int
    {
        $to = CarbonImmutable::parse($toUtc);

        return (int) MonitorIncident::query()
            ->where('started_at', '>=', $fromUtc)
            ->where('started_at', '<=', $to)
            ->get()
            ->sum(function (MonitorIncident $incident) use ($to): int {
                if ($incident->duration_seconds !== null) {
                    return (int) $incident->duration_seconds;
                }

                if (! $incident->started_at) {
                    return 0;
                }

                return (int) max(0, $incident->started_at->diffInSeconds($to));
            });
    }

    private function resolveBaseline(string $timezone): ?CarbonImmutable
    {
        $configured = config('monitor.baseline_started_at');
        if (is_string($configured) && $configured !== '') {
            return CarbonImmutable::parse($configured, $timezone)->utc();
        }

        $earliest = MonitorCheck::query()->orderBy('checked_at')->value('checked_at');

        return $earliest ? CarbonImmutable::parse($earliest) : null;
    }

    /**
     * @param  list<int|float>  $values
     */
    private function percentile(array $values, float $percentile): ?int
    {
        $count = count($values);
        if ($count === 0) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $rank = (int) ceil(($percentile / 100) * $count) - 1;
        $rank = max(0, min($count - 1, $rank));

        return (int) $values[$rank];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRangeUtc(?string $range, string $timezone): array
    {
        $now = CarbonImmutable::now($timezone);

        return match ($range) {
            '7d' => [$now->subDays(7)->utc(), $now->utc()],
            'month' => [$now->startOfMonth()->utc(), $now->endOfMonth()->utc()],
            default => [$now->subDays(30)->utc(), $now->utc()],
        };
    }
}
