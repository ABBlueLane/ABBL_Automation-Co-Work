<?php

namespace App\Services\Line;

class LineCommandParser
{
    public const START = 'start';

    public const STOP = 'stop';

    public const SUBMIT = 'submit';

    public const RESET = 'reset';

    public const MENTION = 'mention';

    public const CONFIRM_CREATE = 'confirm_create';

    public const DECLINE_CREATE = 'decline_create';

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

        $commandText = $this->normalizedCommandText((string) ($message['text'] ?? ''), $message);

        foreach (['เสร็จสิ้น', 'เสร็จแล้ว', 'ยืนยัน'] as $command) {
            if ($commandText === $command || str_contains($commandText, $command)) {
                return self::STOP;
            }
        }

        if (in_array($commandText, ['ส่ง', 'submit'], true)) {
            return self::SUBMIT;
        }

        if (in_array($commandText, ['รีเซ็ต', 'reset'], true)) {
            return self::RESET;
        }

        if ($this->isStructuredLabel($commandText)) {
            return null;
        }

        return self::MENTION;
    }

    public function parseConfirmationReply(string $text): ?string
    {
        $normalized = mb_strtolower(trim($this->stripMentionPrefix($text)));

        if ($normalized === 'สร้าง') {
            return self::CONFIRM_CREATE;
        }

        if ($normalized === 'ไม่สร้าง') {
            return self::DECLINE_CREATE;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function extractMessageBody(array $event): ?string
    {
        if (($event['type'] ?? null) !== 'message') {
            return null;
        }

        $message = $event['message'] ?? [];

        if (($message['type'] ?? null) !== 'text' || ! $this->mentionsSelf($message)) {
            return null;
        }

        return $this->stripMentionsFromText((string) ($message['text'] ?? ''), $message);
    }

    public function pendingInitialMessageFromBody(string $body): ?string
    {
        $body = trim($body);

        if ($body === '') {
            return null;
        }

        $normalized = mb_strtolower($body);

        foreach (['เริ่มเก็บข้อมูล', 'start collecting', 'start'] as $command) {
            if ($normalized === $command) {
                return null;
            }

            if (str_starts_with($normalized, $command.' ')) {
                $body = trim(mb_substr($body, mb_strlen($command)));

                break;
            }
        }

        return $body !== '' ? $body : null;
    }

    /**
     * @return list<string>
     */
    public function structuredLabels(): array
    {
        return ['เรื่อง', 'title', 'ลิงก์', 'link', 'url', 'ความเร่งด่วน', 'priority', 'รายละเอียด', 'detail', 'comment'];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function normalizedCommandText(string $text, array $message): string
    {
        return mb_strtolower($this->stripMentionsFromText($text, $message));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function stripMentionsFromText(string $text, array $message): string
    {
        $mentionees = $message['mention']['mentionees'] ?? [];

        if (! is_array($mentionees) || $mentionees === []) {
            return trim($this->stripMentionPrefix($text));
        }

        $spans = [];

        foreach ($mentionees as $mentionee) {
            if (! is_array($mentionee)) {
                continue;
            }

            $index = (int) ($mentionee['index'] ?? -1);
            $length = (int) ($mentionee['length'] ?? 0);

            if ($index < 0 || $length <= 0) {
                continue;
            }

            $spans[] = ['index' => $index, 'length' => $length];
        }

        if ($spans === []) {
            return trim($this->stripMentionPrefix($text));
        }

        usort($spans, static fn (array $left, array $right): int => $right['index'] <=> $left['index']);

        foreach ($spans as $span) {
            $text = mb_substr($text, 0, $span['index']).mb_substr($text, $span['index'] + $span['length']);
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text;
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

    private function isStructuredLabel(string $commandText): bool
    {
        foreach ($this->structuredLabels() as $label) {
            if (preg_match('/^'.preg_quote($label, '/').'\s*[:：]/iu', $commandText)) {
                return true;
            }
        }

        return false;
    }
}
