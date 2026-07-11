<?php

namespace Tests\Unit;

use App\Services\Line\LineCommandParser;
use PHPUnit\Framework\TestCase;

class LineCommandParserTest extends TestCase
{
    public function test_start_command_requires_self_mention(): void
    {
        $parser = new LineCommandParser;

        $this->assertSame(LineCommandParser::START, $parser->parse($this->textEvent('@ABBL Bot เริ่มเก็บข้อมูล')));
        $this->assertNull($parser->parse($this->textEvent('เริ่มเก็บข้อมูล', mentionsSelf: false)));
    }

    public function test_stop_command_requires_self_mention(): void
    {
        $parser = new LineCommandParser;

        $this->assertSame(LineCommandParser::STOP, $parser->parse($this->textEvent('@ABBL Bot stop')));
        $this->assertNull($parser->parse($this->textEvent('stop', mentionsSelf: false)));
    }

    public function test_submit_and_reset_commands_require_self_mention(): void
    {
        $parser = new LineCommandParser;

        $this->assertSame(LineCommandParser::SUBMIT, $parser->parse($this->textEvent('@ABBL Bot ส่ง')));
        $this->assertSame(LineCommandParser::RESET, $parser->parse($this->textEvent('@ABBL Bot reset')));
        $this->assertNull($parser->parse($this->textEvent('ส่ง', mentionsSelf: false)));
    }

    public function test_structured_label_is_not_treated_as_command(): void
    {
        $parser = new LineCommandParser;

        $this->assertNull($parser->parse($this->textEvent('@ABBL Bot เรื่อง: ระบบล่ม')));
    }

    public function test_non_text_message_is_not_a_command(): void
    {
        $parser = new LineCommandParser;

        $this->assertNull($parser->parse([
            'type' => 'message',
            'message' => ['type' => 'image'],
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function textEvent(string $text, bool $mentionsSelf = true): array
    {
        return [
            'type' => 'message',
            'message' => [
                'type' => 'text',
                'text' => $text,
                'mention' => [
                    'mentionees' => [
                        ['isSelf' => $mentionsSelf],
                    ],
                ],
            ],
        ];
    }
}
