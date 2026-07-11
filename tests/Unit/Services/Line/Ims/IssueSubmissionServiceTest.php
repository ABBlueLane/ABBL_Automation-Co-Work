<?php

namespace Tests\Unit\Services\Line\Ims;

use App\Models\Business;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use App\Services\Line\Ims\IssueSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IssueSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

    private IssueSubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBusiness();
        User::factory()->create(['id' => 1]);
        $this->service = app(IssueSubmissionService::class);
    }

    public function test_create_draft_issue_with_default_title(): void
    {
        $issue = $this->service->createOrUpdateDraft($this->businessId, 1, [
            'comment' => 'รายละเอียด',
            'priority' => Issue::PRIORITY_MEDIUM,
        ]);

        $this->assertSame(Issue::STATUS_DRAFT, $issue->status);
        $this->assertSame('แบบร่าง', $issue->title);
        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $issue->id,
            'comment' => 'รายละเอียด',
        ]);
    }

    public function test_update_existing_draft(): void
    {
        $draft = $this->service->createOrUpdateDraft($this->businessId, 1, [
            'title' => 'หัวข้อเดิม',
            'comment' => 'เดิม',
        ]);

        $updated = $this->service->createOrUpdateDraft($this->businessId, 1, [
            'title' => 'หัวข้อใหม่',
            'comment' => 'ใหม่',
            'url' => 'https://example.com',
            'priority' => Issue::PRIORITY_HIGH,
        ], $draft);

        $this->assertSame($draft->id, $updated->id);
        $this->assertSame('หัวข้อใหม่', $updated->title);
        $this->assertSame('https://example.com', $updated->url);
        $this->assertSame(Issue::PRIORITY_HIGH, $updated->priority);
        $this->assertSame('ใหม่', $updated->firstComment?->comment);
    }

    public function test_submit_draft_changes_status_to_pending(): void
    {
        $draft = $this->service->createOrUpdateDraft($this->businessId, 1, [
            'title' => 'ระบบล่ม',
            'comment' => 'ไม่สามารถ login ได้',
            'priority' => Issue::PRIORITY_HIGH,
        ]);

        $submitted = $this->service->submitDraft($draft, [
            'title' => 'ระบบล่ม',
            'comment' => 'ไม่สามารถ login ได้',
            'url' => null,
            'priority' => Issue::PRIORITY_HIGH,
            'files' => [],
        ]);

        $this->assertSame(Issue::STATUS_PENDING, $submitted->status);
        $this->assertNotSame('DRAFT-', substr($submitted->issue_number, 0, 6));
        $this->assertStringContainsString('IMS', $submitted->issue_number);
    }

    public function test_submit_draft_validates_required_fields(): void
    {
        $draft = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => Issue::generateDraftIssueNumber(),
            'title' => 'แบบร่าง',
            'status' => Issue::STATUS_DRAFT,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => 1,
        ]);

        IssueComment::create([
            'issue_id' => $draft->id,
            'user_id' => 1,
            'comment' => '',
            'files' => [],
        ]);

        $this->expectException(ValidationException::class);

        $this->service->submitDraft($draft, [
            'title' => '',
            'comment' => '',
            'url' => null,
            'priority' => Issue::PRIORITY_MEDIUM,
        ]);
    }

    private function seedBusiness(): void
    {
        Business::unguarded(function (): void {
            Business::create([
                'id' => $this->businessId,
                'business_type' => 1,
                'business_vat_status' => 1,
                'business_branch_status' => 1,
                'business_branch_no' => 0,
                'business_branch_name' => 'สำนักงานใหญ่',
                'business_en_status' => 1,
                'business_name_en' => 'ABBL Automation Co-Work',
                'business_branch_no_en' => 0,
                'business_branch_name_en' => 'Head Office',
                'business_account_finance_year' => 12,
                'business_business_finance_year' => 12,
                'business_code' => 'ABBL',
                'business_name' => 'ABBL Automation Co-Work',
                'business_address1' => 'Bangkok',
                'business_status' => 1,
                'allow_issue' => true,
                'sales_target_amount' => 1000000.00,
            ]);
        });
    }
}
