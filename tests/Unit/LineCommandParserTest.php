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

    public function test_finish_keywords_are_stop_commands(): void
    {
        $parser = new LineCommandParser;

        $this->assertSame(LineCommandParser::STOP, $parser->parse($this->textEvent('@ABBL Bot เสร็จสิ้น')));
        $this->assertSame(LineCommandParser::STOP, $parser->parse($this->textEvent('@ABBL Bot เสร็จแล้ว')));
        $this->assertSame(LineCommandParser::STOP, $parser->parse($this->textEvent('@ABBL Bot ยืนยัน')));
    }

    public function test_bare_mention_is_detected(): void
    {
        $parser = new LineCommandParser;

        $this->assertSame(LineCommandParser::MENTION, $parser->parse($this->textEvent('@ABBL Bot')));
    }

    public function test_confirmation_reply_parser(): void
    {
        $parser = new LineCommandParser;

        $this->assertSame(LineCommandParser::CONFIRM_CREATE, $parser->parseConfirmationReply('สร้าง'));
        $this->assertSame(LineCommandParser::DECLINE_CREATE, $parser->parseConfirmationReply('ไม่สร้าง'));
        $this->assertNull($parser->parseConfirmationReply('hello'));
    }

    public function test_pending_initial_message_from_body(): void
    {
        $parser = new LineCommandParser;

        $this->assertSame(
            'ระบบเข้าใช้งานไม่ได้ ตรวจสอบให้หน่อยครับ',
            $parser->pendingInitialMessageFromBody('ระบบเข้าใช้งานไม่ได้ ตรวจสอบให้หน่อยครับ'),
        );
        $this->assertNull($parser->pendingInitialMessageFromBody(''));
        $this->assertNull($parser->pendingInitialMessageFromBody('เริ่มเก็บข้อมูล'));
        $this->assertSame(
            'ระบบล่ม',
            $parser->pendingInitialMessageFromBody('เริ่มเก็บข้อมูล ระบบล่ม'),
        );
    }

    public function test_extract_message_body_uses_line_mention_indexes(): void
    {
        $parser = new LineCommandParser;

        $body = $parser->extractMessageBody([
            'type' => 'message',
            'message' => [
                'type' => 'text',
                'text' => '@ABBL Automation ระบบเข้าใช้งานไม่ได้',
                'mention' => [
                    'mentionees' => [
                        [
                            'index' => 0,
                            'length' => 17,
                            'isSelf' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('ระบบเข้าใช้งานไม่ได้', $body);
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
