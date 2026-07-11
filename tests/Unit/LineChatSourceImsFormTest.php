<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\Issue;
use App\Models\LineChatSource;
use App\Models\LineImsSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LineChatSourceImsFormTest extends TestCase
{
    use RefreshDatabase;

    private string $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBusiness();
        User::factory()->create(['id' => 1]);
    }

    public function test_line_chat_sources_table_has_ims_form_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('line_chat_sources', [
                'business_id',
                'form_type',
                'draft_issue_id',
                'form_state',
            ])
        );
    }

    public function test_form_state_is_cast_to_array(): void
    {
        $formState = [
            'title' => 'ระบบ login ไม่ได้',
            'priority' => 'medium',
            'url' => null,
            'no_url' => false,
            'comment' => '',
            'files' => [],
            'missing_fields' => ['url_or_no_url'],
        ];

        $source = LineChatSource::create([
            'source_type' => 'group',
            'source_id' => 'group-ims-1',
            'business_id' => $this->businessId,
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
            'form_state' => $formState,
        ]);

        $source->refresh();

        $this->assertIsArray($source->form_state);
        $this->assertSame('ระบบ login ไม่ได้', $source->form_state['title']);
        $this->assertSame($formState, $source->formState());
    }

    public function test_draft_issue_relationship(): void
    {
        $issue = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => Issue::generateDraftIssueNumber(),
            'title' => 'Draft from LINE',
            'status' => Issue::STATUS_DRAFT,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => 1,
        ]);

        $source = LineChatSource::create([
            'source_type' => 'group',
            'source_id' => 'group-ims-2',
            'business_id' => $this->businessId,
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
            'draft_issue_id' => $issue->id,
        ]);

        $this->assertTrue($source->draftIssue->is($issue));
    }

    public function test_business_relationship(): void
    {
        $source = LineChatSource::create([
            'source_type' => 'group',
            'source_id' => 'group-ims-3',
            'business_id' => $this->businessId,
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
        ]);

        $this->assertSame($this->businessId, $source->business->id);
        $this->assertSame('ABBL Automation Co-Work', $source->business->business_name);
    }

    public function test_default_issue_create_form_state(): void
    {
        $defaults = LineChatSource::defaultIssueCreateFormState();

        $this->assertNull($defaults['title']);
        $this->assertSame('medium', $defaults['priority']);
        $this->assertSame(['title', 'url_or_no_url'], $defaults['missing_fields']);
    }

    public function test_line_ims_submissions_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('line_ims_submissions')
        );
    }

    public function test_line_ims_submission_audit_log(): void
    {
        $source = LineChatSource::create([
            'source_type' => 'group',
            'source_id' => 'group-ims-4',
            'business_id' => $this->businessId,
            'form_type' => LineChatSource::FORM_TYPE_ISSUE_CREATE,
        ]);

        $submission = $source->imsSubmissions()->create([
            'webhook_event_id' => 'event-audit-1',
            'status' => LineImsSubmission::STATUS_SUCCESS,
            'form_state' => ['title' => 'test'],
            'submitted_at' => now(),
        ]);

        $this->assertDatabaseHas('line_ims_submissions', [
            'id' => $submission->id,
            'line_chat_source_id' => $source->id,
            'webhook_event_id' => 'event-audit-1',
            'status' => 'success',
        ]);
        $this->assertSame('test', $submission->form_state['title']);
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
