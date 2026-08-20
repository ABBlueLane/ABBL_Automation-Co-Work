<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImsCursorAutofix;
use App\Models\CursorAutofixRun;
use App\Models\Issue;
use App\Services\Cursor\CursorCliClient;
use App\Services\Cursor\ImsCursorAutofixService;
use App\Services\Cursor\RepoTargetResolver;
use Illuminate\Http\JsonResponse;
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

    public function chat(RepoTargetResolver $repoResolver, CursorCliClient $cli): View
    {
        return view('cursor_autofix.chat', [
            'repos' => $repoResolver->listRepos(),
            'binary' => $cli->resolvedBinary(),
            'binaryFound' => $cli->binaryAvailable(),
            'apiKeyConfigured' => $cli->hasApiKey(),
            'messages' => session('cursor_cli_chat', []),
        ]);
    }

    public function testConnection(Request $request, CursorCliClient $cli, RepoTargetResolver $repoResolver): JsonResponse
    {
        $validated = $request->validate([
            'repo_key' => ['nullable', 'string', 'max:100'],
        ]);

        $cwd = base_path();
        $repoKey = trim((string) ($validated['repo_key'] ?? ''));
        if ($repoKey !== '') {
            $repo = $repoResolver->findByKey($repoKey);
            if ($repo === null) {
                return response()->json([
                    'ok' => false,
                    'message' => 'ไม่พบ repository key ที่เลือก',
                ], 422);
            }
            $cwd = $repo['path'];
        }

        $probe = $cli->probeConnection($cwd);

        return response()->json($probe, $probe['ok'] ? 200 : 422);
    }

    public function chatSend(Request $request, CursorCliClient $cli, RepoTargetResolver $repoResolver): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'repo_key' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'in:ask,plan,agent'],
        ]);

        if (! $cli->binaryAvailable()) {
            return response()->json([
                'ok' => false,
                'message' => 'ไม่พบ Cursor CLI binary',
            ], 422);
        }

        $cwd = base_path();
        $repoKey = trim((string) ($validated['repo_key'] ?? ''));
        if ($repoKey !== '') {
            $repo = $repoResolver->findByKey($repoKey);
            if ($repo === null) {
                return response()->json([
                    'ok' => false,
                    'message' => 'ไม่พบ repository key ที่เลือก',
                ], 422);
            }
            if (! is_dir($repo['path'])) {
                return response()->json([
                    'ok' => false,
                    'message' => 'ไม่พบ directory ของ repo: '.$repo['path'],
                ], 422);
            }
            $cwd = $repo['path'];
        }

        $mode = (string) ($validated['mode'] ?? 'ask');
        $force = $mode === 'agent';
        $timeout = (int) config('cursor.cli.chat_timeout', 180);

        $result = $cli->run($validated['message'], $cwd, $timeout, $force, $mode);
        $reply = trim($result['stdout']);
        if ($reply === '') {
            $reply = trim($result['stderr']) !== ''
                ? trim($result['stderr'])
                : 'ไม่มีข้อความตอบกลับจาก CLI';
        }

        $history = session('cursor_cli_chat', []);
        $history[] = [
            'role' => 'user',
            'content' => $validated['message'],
            'at' => now()->toDateTimeString(),
        ];
        $history[] = [
            'role' => 'assistant',
            'content' => $reply,
            'ok' => $result['exit_code'] === 0,
            'mode' => $mode,
            'repo_key' => $repoKey !== '' ? $repoKey : null,
            'at' => now()->toDateTimeString(),
        ];
        $history = array_slice($history, -40);
        session(['cursor_cli_chat' => $history]);

        return response()->json([
            'ok' => $result['exit_code'] === 0,
            'reply' => $reply,
            'exit_code' => $result['exit_code'],
            'timed_out' => $result['timed_out'],
            'command' => $result['command'],
            'cwd' => $cwd,
        ], $result['exit_code'] === 0 ? 200 : 422);
    }

    public function chatClear(): RedirectResponse
    {
        session()->forget('cursor_cli_chat');

        return redirect()
            ->route('cursor_autofix.chat')
            ->with('success', 'ล้างประวัติแชทแล้ว');
    }
}
