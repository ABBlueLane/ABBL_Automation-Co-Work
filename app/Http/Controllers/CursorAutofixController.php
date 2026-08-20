<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImsCursorAutofix;
use App\Models\CursorAutofixRun;
use App\Models\Issue;
use App\Services\Cursor\ImsCursorAutofixService;
use App\Services\Cursor\RepoTargetResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CursorAutofixController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $allowed = ['all', ...array_keys(CursorAutofixRun::statusOptions())];
        if (! in_array($status, $allowed, true)) {
            $status = 'all';
        }

        $runs = CursorAutofixRun::query()
            ->with(['issue:id,issue_number,title,business_id,url,status'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->limit(100)
            ->get();

        return view('cursor_autofix.index', [
            'runs' => $runs,
            'status' => $status,
            'statusOptions' => CursorAutofixRun::statusOptions(),
            'enabled' => (bool) config('cursor.autofix.enabled', false),
            'dryRun' => (bool) config('cursor.autofix.dry_run', true),
        ]);
    }

    public function show(CursorAutofixRun $cursorAutofixRun): View
    {
        $cursorAutofixRun->load(['issue:id,issue_number,title,business_id,url,status,priority']);

        return view('cursor_autofix.show', [
            'run' => $cursorAutofixRun,
            'statusOptions' => CursorAutofixRun::statusOptions(),
        ]);
    }

    public function create(RepoTargetResolver $repoResolver): View
    {
        return view('cursor_autofix.create', [
            'repos' => $repoResolver->listRepos(),
            'enabled' => (bool) config('cursor.autofix.enabled', false),
            'dryRun' => (bool) config('cursor.autofix.dry_run', true),
        ]);
    }

    public function store(
        Request $request,
        ImsCursorAutofixService $service,
        RepoTargetResolver $repoResolver,
    ): RedirectResponse {
        $validated = $request->validate([
            'issue_id' => ['required', 'integer', 'exists:issues,id'],
            'repo_key' => ['nullable', 'string', 'max:100'],
            'sync' => ['sometimes', 'boolean'],
        ]);

        if (! $service->isEnabled()) {
            return back()
                ->withInput()
                ->with('error', 'Cursor Autofix ยังปิดอยู่ ตั้งค่า CURSOR_AUTOFIX_ENABLED=true ก่อน');
        }

        $repoKey = trim((string) ($validated['repo_key'] ?? ''));
        if ($repoKey !== '' && $repoResolver->findByKey($repoKey) === null) {
            return back()
                ->withInput()
                ->with('error', 'ไม่พบ repository key ที่เลือก');
        }

        $issue = Issue::query()->findOrFail((int) $validated['issue_id']);

        if ($issue->status !== Issue::STATUS_PENDING) {
            return back()
                ->withInput()
                ->with('error', 'สั่ง Autofix ได้เฉพาะ issue สถานะ pending (รอรีวิว)');
        }

        $run = $service->queueForIssue(
            $issue,
            $repoKey !== '' ? $repoKey : null,
        );

        if ($run === null) {
            return back()
                ->withInput()
                ->with('error', 'ไม่สามารถสร้างงานได้ (อาจมีงานค้างอยู่แล้ว หรือเงื่อนไขไม่ครบ)');
        }

        if ($run->status === CursorAutofixRun::STATUS_SKIPPED) {
            return redirect()
                ->route('cursor_autofix.show', $run)
                ->with('error', $run->error_message ?: 'ข้ามงานนี้เพราะไม่พบ repo mapping');
        }

        if ($request->boolean('sync')) {
            $run = $service->execute($run);

            return redirect()
                ->route('cursor_autofix.show', $run)
                ->with(
                    $run->status === CursorAutofixRun::STATUS_SUCCEEDED ? 'success' : 'error',
                    $run->status === CursorAutofixRun::STATUS_SUCCEEDED
                        ? 'รัน Autofix เสร็จแล้ว'
                        : ($run->error_message ?: 'รัน Autofix ไม่สำเร็จ'),
                );
        }

        ProcessImsCursorAutofix::dispatch($run->id);

        return redirect()
            ->route('cursor_autofix.show', $run)
            ->with('success', 'ส่งงานเข้าคิวแล้ว');
    }
}
