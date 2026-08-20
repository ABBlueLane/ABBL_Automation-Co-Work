<?php

namespace App\Services\Cursor;

use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

class CursorCliClient
{
    /**
     * @return array{binary: string, argv: list<string>, env: array<string, string>, cwd: string}
     */
    public function buildInvocation(string $prompt, string $workingDirectory): array
    {
        $binary = (string) config('cursor.cli.binary', 'agent');
        $resolved = (new ExecutableFinder)->find($binary) ?? $binary;

        $argv = [$resolved];

        // Some servers expose Cursor as `cursor` (usage: `cursor agent ...`)
        // while others provide a direct `agent` wrapper (usage: `agent -p ...`).
        // Detect by basename and inject the `agent` subcommand when needed.
        $base = strtolower(basename($resolved));
        if ($base === 'cursor') {
            $argv[] = 'agent';
        }

        $argv[] = '-p';

        if ((bool) config('cursor.cli.force', true)) {
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

        $env = [];
        $apiKey = trim((string) config('cursor.cli.api_key', ''));
        if ($apiKey !== '') {
            $env['CURSOR_API_KEY'] = $apiKey;
        }

        return [
            'binary' => $resolved,
            'argv' => $argv,
            'env' => $env,
            'cwd' => $workingDirectory,
        ];
    }

    /**
     * @return array{exit_code: int, stdout: string, stderr: string, timed_out: bool, command: list<string>}
     */
    public function run(string $prompt, string $workingDirectory, ?int $timeoutSeconds = null): array
    {
        $invocation = $this->buildInvocation($prompt, $workingDirectory);
        $timeout = $timeoutSeconds ?? (int) config('cursor.autofix.timeout_seconds', 900);

        $result = Process::path($workingDirectory)
            ->timeout($timeout)
            ->env($invocation['env'])
            ->run($invocation['argv']);

        return [
            'exit_code' => $result->exitCode() ?? 1,
            'stdout' => $result->output(),
            'stderr' => $result->errorOutput(),
            'timed_out' => method_exists($result, 'failed') && $result->exitCode() === null,
            'command' => $invocation['argv'],
        ];
    }

    public function binaryAvailable(): bool
    {
        $binary = (string) config('cursor.cli.binary', 'agent');

        return (new ExecutableFinder)->find($binary) !== null;
    }
}
