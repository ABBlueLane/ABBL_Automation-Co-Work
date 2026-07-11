<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use SplFileObject;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $files = $this->availableLogFiles();
        $file = (string) $request->query('file', $files[0] ?? 'laravel.log');

        if (! in_array($file, $files, true)) {
            abort(404);
        }

        $lines = max(50, min(2000, (int) $request->query('lines', 500)));
        $search = trim((string) $request->query('q', ''));
        $level = (string) $request->query('level', '');

        if (! in_array($level, ['', 'DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
            $level = '';
        }

        $content = $this->readLogContent($file, $lines, $search, $level);
        $fileSize = $this->formatBytes(@filesize(storage_path('logs/'.$file)) ?: 0);
        $updatedAt = @filemtime(storage_path('logs/'.$file));

        return view('logs.index', [
            'files' => $files,
            'file' => $file,
            'lines' => $lines,
            'search' => $search,
            'level' => $level,
            'content' => $content,
            'fileSize' => $fileSize,
            'updatedAt' => $updatedAt ? date('Y-m-d H:i:s', $updatedAt) : '-',
        ]);
    }

    /**
     * @return list<string>
     */
    private function availableLogFiles(): array
    {
        $paths = glob(storage_path('logs/*.log')) ?: [];

        $files = array_map(static fn (string $path): string => basename($path), $paths);
        rsort($files);

        if ($files === []) {
            return ['laravel.log'];
        }

        return array_values($files);
    }

    private function readLogContent(string $file, int $maxLines, string $search, string $level): string
    {
        $path = storage_path('logs/'.$file);

        if (! is_file($path)) {
            return 'ไม่พบไฟล์ log';
        }

        $bufferLines = ($search !== '' || $level !== '') ? $maxLines * 20 : $maxLines;
        $lines = $this->tailLines($path, $bufferLines);

        if ($search !== '' || $level !== '') {
            $lines = array_values(array_filter($lines, function (string $line) use ($search, $level): bool {
                if ($level !== '' && stripos($line, ".{$level}:") === false) {
                    return false;
                }

                if ($search !== '' && stripos($line, $search) === false) {
                    return false;
                }

                return true;
            }));

            $lines = array_slice($lines, -$maxLines);
        }

        if ($lines === []) {
            return 'ไม่พบ log ตามเงื่อนไขที่เลือก';
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function tailLines(string $path, int $lines): array
    {
        $file = new SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $start = max(0, $lastLine - $lines);
        $file->seek($start);

        $result = [];

        while (! $file->eof()) {
            $line = $file->current();

            if (is_string($line)) {
                $result[] = rtrim($line, "\r\n");
            }

            $file->next();
        }

        return $result;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
