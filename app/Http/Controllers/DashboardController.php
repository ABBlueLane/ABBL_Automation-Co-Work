<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(string $business): View
    {
        return view('office.dashboard.issue');
    }

    public function issue(string $business): View
    {
        return view('office.dashboard.issue');
    }

    public function issueStats(string $business): JsonResponse
    {
        $base = Issue::query()
            ->where('business_id', $business)
            ->where('status', '!=', Issue::STATUS_DRAFT);

        $total = (clone $base)->count();
        $unassigned = (clone $base)->whereNull('assigned_to')->count();
        $done = (clone $base)->where('status', Issue::STATUS_DONE)->count();
        $open = (clone $base)->where('status', '!=', Issue::STATUS_DONE)->count();

        $statusKeys = [
            Issue::STATUS_PENDING,
            Issue::STATUS_IN_PROGRESS,
            Issue::STATUS_WAITING_REVIEW,
            Issue::STATUS_CUSTOMER_REPLIED,
            Issue::STATUS_DONE,
        ];

        $statusCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $byStatus = [];
        foreach ($statusKeys as $key) {
            $byStatus[] = [
                'key' => $key,
                'label' => Issue::getStatusMeta($key)['label'],
                'count' => (int) ($statusCounts[$key] ?? 0),
            ];
        }

        $priorityCounts = (clone $base)
            ->where('status', '!=', Issue::STATUS_DONE)
            ->selectRaw('priority, COUNT(*) as c')
            ->groupBy('priority')
            ->pluck('c', 'priority');
        $byPriority = [];
        foreach (array_keys(Issue::getPriorityOptions()) as $key) {
            $byPriority[] = [
                'key' => $key,
                'label' => Issue::getPriorityOptions()[$key],
                'count' => (int) ($priorityCounts[$key] ?? 0),
            ];
        }

        $monthlyRows = Issue::query()
            ->where('business_id', $business)
            ->where('status', '!=', Issue::STATUS_DRAFT)
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as c')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', (int) $r->y, (int) $r->m));

        $createdByMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i)->startOfMonth();
            $k = sprintf('%04d-%02d', $d->year, $d->month);
            $createdByMonth[] = ['label' => $d->format('m/Y'), 'count' => (int) (optional($monthlyRows->get($k))->c ?? 0)];
        }

        $completedRows = Issue::query()
            ->where('business_id', $business)
            ->where('status', '!=', Issue::STATUS_DRAFT)
            ->whereNotNull('complete_at')
            ->where('complete_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('YEAR(complete_at) as y, MONTH(complete_at) as m, COUNT(*) as c')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', (int) $r->y, (int) $r->m));

        $completedByMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i)->startOfMonth();
            $k = sprintf('%04d-%02d', $d->year, $d->month);
            $completedByMonth[] = ['label' => $d->format('m/Y'), 'count' => (int) (optional($completedRows->get($k))->c ?? 0)];
        }

        $topRows = Issue::query()
            ->where('business_id', $business)
            ->where('status', '!=', Issue::STATUS_DRAFT)
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as issue_count,
                SUM(CASE WHEN status NOT IN (?, ?) THEN 1 ELSE 0 END) as unfinished_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as waiting_review_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as done_count', [
                Issue::STATUS_WAITING_REVIEW,
                Issue::STATUS_DONE,
                Issue::STATUS_WAITING_REVIEW,
                Issue::STATUS_DONE,
            ])
            ->groupBy('assigned_to')
            ->orderByDesc('issue_count')
            ->limit(10)
            ->get();

        $users = User::whereIn('id', $topRows->pluck('assigned_to'))->get()->keyBy('id');
        $topAssignees = $topRows->map(function ($row) use ($users) {
            $u = $users->get($row->assigned_to);

            return [
                'name' => $u ? $u->full_name : '#'.$row->assigned_to,
                'count' => (int) $row->issue_count,
                'unfinished' => (int) $row->unfinished_count,
                'waiting_review' => (int) $row->waiting_review_count,
                'done' => (int) $row->done_count,
            ];
        })->values();

        $calendarEvents = (clone $base)
            ->with('assignee')
            ->whereNotNull('planned_start_at')
            ->whereNotNull('due_at')
            ->get()
            ->map(function (Issue $issue) {
                $isOverdue = $issue->status !== Issue::STATUS_DONE && $issue->due_at && now()->gt($issue->due_at);

                return [
                    'id' => $issue->id,
                    'title' => $issue->issue_number.' - '.$issue->title,
                    'start' => optional($issue->planned_start_at)->toIso8601String(),
                    'end' => optional($issue->due_at)->toIso8601String(),
                    'color' => $isOverdue ? '#dc3545' : '#0d6efd',
                    'extendedProps' => [
                        'status' => Issue::getStatusMeta($issue->status)['label'],
                        'assignee' => $issue->assignee?->full_name ?? '-',
                        'planned_start_at' => optional($issue->planned_start_at)->format('d/m/Y H:i'),
                        'due_at' => optional($issue->due_at)->format('d/m/Y H:i'),
                    ],
                ];
            })->values();

        return response()->json([
            'total' => $total,
            'open' => $open,
            'unassigned' => $unassigned,
            'done' => $done,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'created_by_month' => $createdByMonth,
            'completed_by_month' => $completedByMonth,
            'top_assignees' => $topAssignees,
            'calendar_events' => $calendarEvents,
        ]);
    }

    public function issueModal(string $business, int $id): View
    {
        $issue = Issue::with(['creator', 'assignee', 'firstComment'])
            ->where('business_id', $business)
            ->where('status', '!=', Issue::STATUS_DRAFT)
            ->findOrFail($id);

        return view('office.dashboard.ajax.modalIssueDetail', compact('issue'));
    }
}
