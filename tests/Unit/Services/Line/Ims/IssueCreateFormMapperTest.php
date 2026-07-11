<?php

namespace Tests\Unit\Services\Line\Ims;

use App\Models\Issue;
use App\Services\Line\Ims\IssueCreateFormCompleter;
use App\Services\Line\Ims\IssueCreateFormMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IssueCreateFormMapperTest extends TestCase
{
    private IssueCreateFormMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new IssueCreateFormMapper;
    }

    public function test_first_message_sets_title(): void
    {
        $result = $this->mapper->mapTextMessage('ระบบ login ไม่ได้', [
            'title' => null,
            'comment' => '',
        ]);

        $this->assertSame('ระบบ login ไม่ได้', $result['updates']['title']);
        $this->assertNull($result['action']);
    }

    public function test_subsequent_message_appends_comment(): void
    {
        $result = $this->mapper->mapTextMessage('กดแล้ว error 500', [
            'title' => 'ระบบ login ไม่ได้',
            'comment' => 'เดิม',
        ]);

        $this->assertSame("เดิม\nกดแล้ว error 500", $result['updates']['comment']);
    }

    public function test_extract_url_from_text(): void
    {
        $this->assertSame(
            'https://example.com/page',
            $this->mapper->extractUrl('ดูที่ https://example.com/page ครับ')
        );
    }

    public function test_rejects_unsafe_url_scheme(): void
    {
        $this->assertNull($this->mapper->extractUrl('javascript:alert(1)'));
    }

    public function test_detect_no_url_intent(): void
    {
        $this->assertTrue($this->mapper->detectNoUrlIntent('ไม่มี url'));
        $this->assertTrue($this->mapper->detectNoUrlIntent('no url'));
    }

    public function test_detect_priority_keywords(): void
    {
        $this->assertSame(Issue::PRIORITY_HIGH, $this->mapper->detectPriority('เร่งด่วนมาก'));
        $this->assertSame(Issue::PRIORITY_LOW, $this->mapper->detectPriority('ความสำคัญต่ำ'));
        $this->assertSame(Issue::PRIORITY_MEDIUM, $this->mapper->detectPriority('ปกติ'));
    }

    public function test_structured_label_maps_title(): void
    {
        $result = $this->mapper->mapTextMessage('@OA เรื่อง: ระบบ login ไม่ได้', []);

        $this->assertSame('ระบบ login ไม่ได้', $result['updates']['title']);
    }

    public function test_structured_label_maps_url(): void
    {
        $result = $this->mapper->mapTextMessage('@OA ลิงก์: https://example.com', []);

        $this->assertSame('https://example.com', $result['updates']['url']);
        $this->assertFalse($result['updates']['no_url']);
    }

    public function test_submit_and_reset_actions(): void
    {
        $this->assertSame(
            IssueCreateFormMapper::ACTION_SUBMIT,
            $this->mapper->mapTextMessage('@OA ส่ง', [])['action']
        );
        $this->assertSame(
            IssueCreateFormMapper::ACTION_RESET,
            $this->mapper->mapTextMessage('@OA รีเซ็ต', [])['action']
        );
    }

    public function test_multiline_message_splits_title_and_comment(): void
    {
        $result = $this->mapper->mapTextMessage("ระบบล่ม\nกด login ไม่ได้", [
            'title' => null,
            'comment' => '',
        ]);

        $this->assertSame('ระบบล่ม', $result['updates']['title']);
        $this->assertSame('กด login ไม่ได้', $result['updates']['comment']);
    }
}

class IssueCreateFormCompleterTest extends TestCase
{
    private IssueCreateFormCompleter $completer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->completer = new IssueCreateFormCompleter;
    }

    #[DataProvider('incompleteStatesProvider')]
    public function test_missing_fields(array $state, array $expectedMissing): void
    {
        $this->assertSame($expectedMissing, $this->completer->missingFields($state));
        $this->assertFalse($this->completer->isComplete($state));
    }

    #[DataProvider('completeStatesProvider')]
    public function test_complete_states(array $state): void
    {
        $this->assertSame([], $this->completer->missingFields($state));
        $this->assertTrue($this->completer->isComplete($state));
    }

    public static function incompleteStatesProvider(): array
    {
        return [
            'missing title and url' => [[
                'title' => null,
                'url' => null,
                'no_url' => false,
            ], ['title', 'url_or_no_url']],
            'missing url only' => [[
                'title' => 'มีหัวข้อ',
                'url' => null,
                'no_url' => false,
            ], ['url_or_no_url']],
        ];
    }

    public static function completeStatesProvider(): array
    {
        return [
            'with url' => [[
                'title' => 'มีหัวข้อ',
                'url' => 'https://example.com',
                'no_url' => false,
            ]],
            'with no_url flag' => [[
                'title' => 'มีหัวข้อ',
                'url' => null,
                'no_url' => true,
            ]],
        ];
    }
}
