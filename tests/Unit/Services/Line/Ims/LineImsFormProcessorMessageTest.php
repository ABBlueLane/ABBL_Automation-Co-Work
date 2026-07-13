<?php

namespace Tests\Unit\Services\Line\Ims;

use App\Models\Issue;
use App\Services\Line\Ims\LineImsFormProcessor;
use Tests\TestCase;

class LineImsFormProcessorMessageTest extends TestCase
{
    public function test_success_message_uses_public_base_url(): void
    {
        config()->set('services.line.ims.public_base_url', 'https://co-work.bluelane.co.th');

        $issue = new Issue([
            'issue_number' => 'ABBL-IMS202607-000004',
        ]);
        $issue->id = 4;

        $processor = app(LineImsFormProcessor::class);

        $this->assertSame(
            'https://co-work.bluelane.co.th/issue/view/4',
            $processor->issueViewUrl('9c9aafbc-f74a-4e30-b44a-1209b30431ad', 4),
        );

        $message = $processor->successMessage($issue, '9c9aafbc-f74a-4e30-b44a-1209b30431ad');

        $this->assertStringContainsString('https://co-work.bluelane.co.th/issue/view/4', $message);
        $this->assertStringContainsString('กรุณาตรวจสอบและรีวิวรายละเอียด:', $message);
    }
}
