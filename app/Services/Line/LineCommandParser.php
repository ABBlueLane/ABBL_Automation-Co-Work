<?php

namespace App\Services\Line;

class LineCommandParser
{
    public const START = 'start';

    public const STOP = 'stop';

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

        return null;
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
