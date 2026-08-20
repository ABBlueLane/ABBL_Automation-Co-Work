<?php

namespace App\Services\Cursor;

use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

class CursorCliClient
{
    public function resolvedBinary(): string
    {
        $binary = (string) config('cursor.cli.binary', 'agent');

        if ($this->isAbsoluteExecutable($binary)) {
            return $binary;
        }

        return (new ExecutableFinder)->find($binary) ?? $binary;
    }

    public function binaryAvailable(): bool
    {
        $binary = (string) config('cursor.cli.binary', 'agent');

        if ($this->isAbsoluteExecutable($binary)) {
            return true;
        }

        return (new ExecutableFinder)->find($binary) !== null;
    }

    private function isAbsoluteExecutable(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        if (! str_starts_with($binary, DIRECTORY_SEPARATOR) && ! preg_match('#^[A-Za-z]:[\\\\/]#', $binary)) {
            return false;
        }

        return is_file($binary) && is_executable($binary);
    }

    public function hasApiKey(): bool
    {
        return trim((string) config('cursor.cli.api_key', '')) !== '';
    }

    /**
     * @return list<string>
     */
    public function agentBaseArgv(): array
    {
        $resolved = $this->resolvedBinary();
        $argv = [$resolved];

        if (strtolower(basename($resolved)) === 'cursor') {
            $argv[] = 'agent';
        }

        return $argv;
    }

    /**
     * @return array{binary: string, argv: list<string>, env: array<string, string>, cwd: string}
     */
    public function buildInvocation(
        string $prompt,
        string $workingDirectory,
        bool $force = true,
        string $mode = 'agent',
    ): array {
        $argv = $this->agentBaseArgv();
        $argv[] = '-p';

        if ($mode === 'ask') {
            $argv[] = '--mode';
            $argv[] = 'ask';
        } elseif ($mode === 'plan') {
            $argv[] = '--mode';
            $argv[] = 'plan';
        }

        if ($force && $mode === 'agent') {
            $argv[] = '--force';
        }

        $outputFormat = (string) config('cursor.cli.output_format', 'text');
        if ($outputFormat !== '') {
            $argv[] = '--output-format';
            $argv[] = $outputFormat;
        }

        $model = trim((string) config('cursor.cli.model', ''));
        if ($model !== '') {
            $argv[] = '--model';
            $argv[] = $model;
        }

        $argv[] = $prompt;

        return [
            'binary' => $this->resolvedBinary(),
            'argv' => $argv,
            'env' => $this->cliEnv(),
            'cwd' => $workingDirectory,
        ];
    }

    /**
     * @return array{exit_code: int, stdout: string, stderr: string, timed_out: bool, command: list<string>}
     */
    public function run(
        string $prompt,
        string $workingDirectory,
        ?int $timeoutSeconds = null,
        bool $force = true,
        string $mode = 'agent',
    ): array {
        $invocation = $this->buildInvocation($prompt, $workingDirectory, $force, $mode);
        $timeout = $timeoutSeconds ?? (int) config('cursor.autofix.timeout_seconds', 900);

        try {
            $result = Process::path($workingDirectory)
                ->timeout($timeout)
                ->env($invocation['env'])
                ->run($invocation['argv']);

            return [
                'exit_code' => $result->exitCode() ?? 1,
                'stdout' => $result->output(),
                'stderr' => $result->errorOutput(),
                'timed_out' => false,
                'command' => $invocation['argv'],
            ];
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $exception) {
            return [
                'exit_code' => 124,
                'stdout' => '',
                'stderr' => $exception->getMessage(),
                'timed_out' => true,
                'command' => $invocation['argv'],
            ];
        }
    }

    /**
     * Lightweight connectivity / auth probe against Cursor CLI.
     *
     * @return array{
     *     ok: bool,
     *     binary_found: bool,
     *     binary: string,
     *     api_key_configured: bool,
     *     version: string|null,
     *     status_output: string|null,
     *     ping_ok: bool|null,
     *     ping_output: string|null,
     *     message: string,
     *     checks: list<array{name: string, ok: bool, detail: string}>
     * }
     */
    public function probeConnection(?string $workingDirectory = null): array
    {
        $cwd = $workingDirectory ?: base_path();
        $checks = [];
        $binary = $this->resolvedBinary();
        $binaryFound = $this->binaryAvailable();

        $checks[] = [
            'name' => 'binary',
            'ok' => $binaryFound,
            'detail' => $binaryFound
                ? "พบ binary: {$binary}"
                : 'ไม่พบ Cursor CLI binary (ตั้งค่า CURSOR_CLI_BINARY)',
        ];

        $apiKeyConfigured = $this->hasApiKey();
        $checks[] = [
            'name' => 'api_key',
            'ok' => $apiKeyConfigured,
            'detail' => $apiKeyConfigured
                ? 'ตั้งค่า CURSOR_API_KEY แล้ว'
                : 'ยังไม่มี CURSOR_API_KEY',
        ];

        $version = null;
        $statusOutput = null;
        $pingOk = null;
        $pingOutput = null;

        if ($binaryFound) {
            $versionResult = $this->runAgentCommand(['--version'], $cwd, 20);
            $version = trim($versionResult['stdout']."\n".$versionResult['stderr']) ?: null;
            $checks[] = [
                'name' => 'version',
                'ok' => $versionResult['exit_code'] === 0 || filled($version),
                'detail' => $version ?: 'อ่านเวอร์ชันไม่ได้',
            ];

            $statusResult = $this->runAgentCommand(['status'], $cwd, 30);
            $statusOutput = trim($statusResult['stdout']."\n".$statusResult['stderr']) ?: null;
            $statusOk = $statusResult['exit_code'] === 0
                && $statusOutput !== null
                && ! str_contains(mb_strtolower($statusOutput), 'authentication required')
                && ! str_contains(mb_strtolower($statusOutput), 'not logged in');

            $checks[] = [
                'name' => 'auth_status',
                'ok' => $statusOk,
                'detail' => $statusOutput ?: ('exit '.$statusResult['exit_code']),
            ];

            if ($apiKeyConfigured || $statusOk) {
                $ping = $this->run(
                    'Reply with exactly: CLI_OK',
                    $cwd,
                    (int) config('cursor.cli.probe_timeout', 60),
                    force: false,
                    mode: 'ask',
                );
                $pingOutput = trim($ping['stdout']."\n".$ping['stderr']);
                $pingOk = $ping['exit_code'] === 0 && str_contains($ping['stdout'], 'CLI_OK');
                $checks[] = [
                    'name' => 'ping',
                    'ok' => (bool) $pingOk,
                    'detail' => $pingOk
                        ? 'ตอบกลับจาก agent สำเร็จ'
                        : ($pingOutput !== '' ? $pingOutput : 'ping ไม่สำเร็จ'),
                ];
            }
        }

        if (! $binaryFound) {
            $message = 'ไม่พบ Cursor CLI บนเครื่อง';
            $ok = false;
        } else {
            $authOk = (bool) (collect($checks)->firstWhere('name', 'auth_status')['ok'] ?? false);
            $pingCheckOk = (bool) (collect($checks)->firstWhere('name', 'ping')['ok'] ?? false);
            $ok = $authOk || $pingCheckOk;

            if ($ok) {
                $message = 'เชื่อมต่อ Cursor CLI ได้';
            } elseif (! $apiKeyConfigured && ! $authOk) {
                $message = 'พบ CLI แล้ว แต่ยังไม่ได้ login / ยังไม่มี CURSOR_API_KEY';
            } else {
                $message = 'พบ CLI แล้ว แต่ทดสอบเรียก agent ยังไม่สำเร็จ';
            }
        }

        return [
            'ok' => $ok,
            'binary_found' => $binaryFound,
            'binary' => $binary,
            'api_key_configured' => $apiKeyConfigured,
            'version' => $version,
            'status_output' => $statusOutput,
            'ping_ok' => $pingOk,
            'ping_output' => $pingOutput,
            'message' => $message,
            'checks' => $checks,
        ];
    }

    /**
     * @param  list<string>  $args
     * @return array{exit_code: int, stdout: string, stderr: string, command: list<string>}
     */
    public function runAgentCommand(array $args, string $workingDirectory, int $timeoutSeconds = 30): array
    {
        $argv = array_merge($this->agentBaseArgv(), $args);

        try {
            $result = Process::path($workingDirectory)
                ->timeout($timeoutSeconds)
                ->env($this->cliEnv())
                ->run($argv);

            return [
                'exit_code' => $result->exitCode() ?? 1,
                'stdout' => $result->output(),
                'stderr' => $result->errorOutput(),
                'command' => $argv,
            ];
        } catch (\Throwable $exception) {
            return [
                'exit_code' => 1,
                'stdout' => '',
                'stderr' => $exception->getMessage(),
                'command' => $argv,
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function cliEnv(): array
    {
        $env = [];
        $apiKey = trim((string) config('cursor.cli.api_key', ''));
        if ($apiKey !== '') {
            $env['CURSOR_API_KEY'] = $apiKey;
        }

        return $env;
    }
}
