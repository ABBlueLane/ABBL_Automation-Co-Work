<?php

namespace App\Services\Line;

class LineCommandParser
{
    public const START = 'start';

    public const STOP = 'stop';

    public const SUBMIT = 'submit';

    public const RESET = 'reset';

    /**
     * @param  array<string, mixed>  $event
     */
    public function parse(array $event): ?string
    {
        if (($event['type'] ?? null) !== 'message') {
            return null;
        }

        $message = $event['message'] ?? [];

        if (($message['type'] ?? null) !== 'text' || ! $this->mentionsSelf($message)) {
            return null;
        }

        $text = mb_strtolower(trim((string) ($message['text'] ?? '')));

        foreach (['เริ่มเก็บข้อมูล', 'start collecting', 'start'] as $command) {
            if (str_contains($text, $command)) {
                return self::START;
            }
        }

        foreach (['หยุดเก็บข้อมูล', 'stop collecting', 'stop'] as $command) {
            if (str_contains($text, $command)) {
                return self::STOP;
            }
        }

        $commandText = $this->normalizedCommandText($text);

        if (in_array($commandText, ['ส่ง', 'submit'], true)) {
            return self::SUBMIT;
        }

        if (in_array($commandText, ['รีเซ็ต', 'reset'], true)) {
            return self::RESET;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function structuredLabels(): array
    {
        return ['เรื่อง', 'title', 'ลิงก์', 'link', 'url', 'ความเร่งด่วน', 'priority', 'รายละเอียด', 'detail', 'comment'];
    }

    private function normalizedCommandText(string $text): string
    {
        return mb_strtolower($this->stripMentionPrefix($text));
    }

    private function stripMentionPrefix(string $text): string
    {
        if (preg_match('/^@oa\s+/iu', $text)) {
            return trim((string) preg_replace('/^@oa\s+/iu', '', $text));
        }

        if (preg_match('/^@\S+\s+Bot\s+/iu', $text)) {
            return trim((string) preg_replace('/^@\S+\s+Bot\s+/iu', '', $text));
        }

        return trim((string) preg_replace('/^@\S+\s+/u', '', $text));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function mentionsSelf(array $message): bool
    {
        $mentionees = $message['mention']['mentionees'] ?? [];

        if (! is_array($mentionees)) {
            return false;
        }

        foreach ($mentionees as $mentionee) {
            if (is_array($mentionee) && ($mentionee['isSelf'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }
}
