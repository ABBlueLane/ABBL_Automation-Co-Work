<?php

namespace App\Services\Line\Ims;

use App\Models\Issue;

class IssueCreateFormMapper
{
    public const ACTION_SUBMIT = 'submit';

    public const ACTION_RESET = 'reset';

    /**
     * @var array<string, string>
     */
    private const STRUCTURED_LABELS = [
        'เรื่อง' => 'title',
        'title' => 'title',
        'ลิงก์' => 'url',
        'link' => 'url',
        'url' => 'url',
        'ความเร่งด่วน' => 'priority',
        'priority' => 'priority',
        'รายละเอียด' => 'comment',
        'detail' => 'comment',
        'comment' => 'comment',
    ];

    /**
     * @param  array<string, mixed>  $currentState
     * @return array{updates: array<string, mixed>, action: string|null}
     */
    public function mapTextMessage(string $text, array $currentState): array
    {
        $text = trim($text);

        if ($text === '') {
            return ['updates' => [], 'action' => null];
        }

        if ($this->detectSubmitIntent($text)) {
            return ['updates' => [], 'action' => self::ACTION_SUBMIT];
        }

        if ($this->detectResetIntent($text)) {
            return ['updates' => [], 'action' => self::ACTION_RESET];
        }

        $structured = $this->parseStructuredField($text);
        if ($structured !== null) {
            return [
                'updates' => $this->mapStructuredValue($structured['field'], $structured['value'], $currentState),
                'action' => null,
            ];
        }

        return [
            'updates' => $this->mapHeuristicMessage($text, $currentState),
            'action' => null,
        ];
    }

    public function extractUrl(string $text): ?string
    {
        if (! preg_match('#\bhttps?://[^\s<>"\'\]]+#iu', $text, $matches)) {
            return null;
        }

        $url = rtrim($matches[0], '.,;:!?)');

        if (! $this->isSafeHttpUrl($url)) {
            return null;
        }

        return $url;
    }

    public function detectPriority(string $text): ?string
    {
        $normalized = mb_strtolower(trim($text));

        if (preg_match('/(เร่งด่วน|urgent|ด่วน|\bhigh\b)/iu', $normalized)) {
            return Issue::PRIORITY_HIGH;
        }

        if (preg_match('/(ต่ำ|\blow\b)/iu', $normalized)) {
            return Issue::PRIORITY_LOW;
        }

        if (preg_match('/(ปกติ|normal|\bmedium\b)/iu', $normalized)) {
            return Issue::PRIORITY_MEDIUM;
        }

        return null;
    }

    public function detectNoUrlIntent(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        return (bool) preg_match('/\b(ไม่มี\s*url|no\s*url|ไม่มี\s*ลิงก์|ไม่มีลิงก์)\b/u', $normalized);
    }

    public function detectSubmitIntent(string $text): bool
    {
        $normalized = mb_strtolower(trim($this->stripOaPrefix($text)));

        return (bool) preg_match('/^(ส่ง|submit)$/u', $normalized);
    }

    public function detectResetIntent(string $text): bool
    {
        $normalized = mb_strtolower(trim($this->stripOaPrefix($text)));

        return (bool) preg_match('/^(รีเซ็ต|reset)$/u', $normalized);
    }

    /**
     * @param  array<string, mixed>  $currentState
     * @return array<string, mixed>
     */
    private function mapHeuristicMessage(string $text, array $currentState): array
    {
        $updates = [];

        if ($this->detectNoUrlIntent($text)) {
            $updates['no_url'] = true;
            $updates['url'] = null;
        }

        $priority = $this->detectPriority($text);
        if ($priority !== null) {
            $updates['priority'] = $priority;
        }

        $url = $this->extractUrl($text);
        if ($url !== null) {
            $updates['url'] = $url;
            $updates['no_url'] = false;
        }

        $title = trim((string) ($currentState['title'] ?? ''));
        $contentText = $text;

        if ($url !== null) {
            $contentText = trim(str_replace($url, '', $contentText));
        }

        if ($contentText === '') {
            return $updates;
        }

        if ($title === '') {
            $lines = preg_split("/\r\n|\n|\r/", $contentText, 2) ?: [$contentText];
            $firstLine = trim((string) ($lines[0] ?? ''));
            $rest = trim((string) ($lines[1] ?? ''));

            if ($firstLine !== '') {
                $updates['title'] = mb_substr($firstLine, 0, 255);
            }

            if ($rest !== '') {
                $updates['comment'] = $this->appendComment((string) ($currentState['comment'] ?? ''), $rest);
            }

            return $updates;
        }

        $updates['comment'] = $this->appendComment((string) ($currentState['comment'] ?? ''), $contentText);

        return $updates;
    }

    /**
     * @return array{field: string, value: string}|null
     */
    private function parseStructuredField(string $text): ?array
    {
        $normalized = trim($this->stripOaPrefix($text));

        foreach (self::STRUCTURED_LABELS as $label => $field) {
            $pattern = '/^'.preg_quote($label, '/').'\s*[:：]\s*(.+)$/iu';

            if (preg_match($pattern, $normalized, $matches)) {
                return [
                    'field' => $field,
                    'value' => trim((string) $matches[1]),
                ];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $currentState
     * @return array<string, mixed>
     */
    private function mapStructuredValue(string $field, string $value, array $currentState): array
    {
        return match ($field) {
            'title' => ['title' => mb_substr($value, 0, 255)],
            'url' => $this->mapUrlValue($value),
            'priority' => ['priority' => $this->resolvePriorityValue($value)],
            'comment' => ['comment' => $this->appendComment((string) ($currentState['comment'] ?? ''), $value)],
            default => [],
        };
    }

    /**
     * @return array{url: null, no_url: true}|array{url: string, no_url: false}
     */
    private function mapUrlValue(string $value): array
    {
        if ($this->detectNoUrlIntent($value)) {
            return ['url' => null, 'no_url' => true];
        }

        $url = $this->extractUrl($value) ?? ($this->isSafeHttpUrl($value) ? $value : null);

        if ($url === null) {
            return [];
        }

        return ['url' => $url, 'no_url' => false];
    }

    private function resolvePriorityValue(string $value): string
    {
        return $this->detectPriority($value) ?? Issue::PRIORITY_MEDIUM;
    }

    private function appendComment(string $existing, string $addition): string
    {
        $addition = trim($addition);

        if ($addition === '') {
            return $existing;
        }

        if ($existing === '') {
            return $addition;
        }

        return $existing."\n".$addition;
    }

    private function stripOaPrefix(string $text): string
    {
        if (preg_match('/^@oa\s+/iu', $text)) {
            return trim((string) preg_replace('/^@oa\s+/iu', '', $text));
        }

        if (preg_match('/^@\S+\s+Bot\s+/iu', $text)) {
            return trim((string) preg_replace('/^@\S+\s+Bot\s+/iu', '', $text));
        }

        return trim((string) preg_replace('/^@\S+\s+/u', '', $text));
    }

    private function isSafeHttpUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
