<?php

namespace App\Http\Controllers;

use App\Models\MonitorCheck;
use App\Models\MonitorIncident;
use App\Models\MonitorTarget;
use App\Services\Monitor\MonitorCheckService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function index(): View
    {
        return view('monitor.index', [
            'displayTimezone' => config('monitor.timezone_display', 'Asia/Bangkok'),
        ]);
    }

    public function status(): JsonResponse
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $targets = MonitorTarget::query()
            ->orderBy('id')
            ->get();

        $rows = $targets->map(function (MonitorTarget $target) use ($timezone): array {
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

            $status = 'unknown';
            if ($openIncident) {
                $status = 'down';
            } elseif (! $target->is_active) {
                $status = 'inactive';
            } elseif ($latestCheck) {
                $status = $latestCheck->ok ? 'up' : 'degraded';
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

        return response()->json([
            'timezone_display' => $timezone,
            'targets' => $rows,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $nowInTimezone = CarbonImmutable::now($timezone);

        $todayStartUtc = $nowInTimezone->startOfDay()->utc();
        $todayEndUtc = $nowInTimezone->endOfDay()->utc();

        $monthStartUtc = $nowInTimezone->startOfMonth()->utc();
        $monthEndUtc = $nowInTimezone->endOfMonth()->utc();

        $sevenDaysAgoUtc = $nowInTimezone->subDays(7)->utc();

        $incidentsToday = MonitorIncident::query()
            ->where('started_at', '>=', $todayStartUtc)
            ->where('started_at', '<=', $todayEndUtc)
            ->count();

        $incidents7d = MonitorIncident::query()
            ->where('started_at', '>=', $sevenDaysAgoUtc)
            ->count();

        $incidentsMonth = MonitorIncident::query()
            ->where('started_at', '>=', $monthStartUtc)
            ->where('started_at', '<=', $monthEndUtc)
            ->count();

        $downtime7d = MonitorIncident::query()
            ->where('started_at', '>=', $sevenDaysAgoUtc)
            ->get()
            ->sum(function (MonitorIncident $incident): int {
                if ($incident->duration_seconds !== null) {
                    return (int) $incident->duration_seconds;
                }

                return (int) max(0, $incident->started_at?->diffInSeconds(now()) ?? 0);
            });

        return response()->json([
            'timezone_display' => $timezone,
            'range' => $request->string('range')->toString(),
            'totals' => [
                'incidents_today' => $incidentsToday,
                'incidents_7d' => $incidents7d,
                'incidents_month' => $incidentsMonth,
                'downtime_seconds_7d' => (int) $downtime7d,
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

        return response()->json([
            'range' => $request->string('range')->toString() ?: '30d',
            'timezone_display' => $timezone,
            'hours' => $series,
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
