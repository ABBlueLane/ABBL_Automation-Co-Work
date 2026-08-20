<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueCreateDraftSubmitTest extends TestCase
{
    use RefreshDatabase;

    private string $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

    protected function setUp(): void
    {
        parent::setUp();

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

        config()->set('services.line.ims.default_business_id', $this->businessId);
    }

    public function test_create_page_uses_correct_draft_submit_route_template(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['mainBusinessID' => $this->businessId])
            ->get(route('issue.create'))
            ->assertOk()
            ->assertSee('draftSubmitUrlTemplate', false)
            ->assertSee('__DRAFT_ID__', false)
            ->assertDontSee('/issue/my/" + draftIssueId + "/submit', false)
            ->assertDontSee("issueIndexBase", false);
    }

    public function test_owner_can_submit_draft_via_issue_submit_route(): void
    {
        $user = User::factory()->create();
        $draft = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => Issue::generateDraftIssueNumber(),
            'title' => 'Draft title',
            'status' => Issue::STATUS_DRAFT,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => $user->id,
        ]);

        IssueComment::create([
            'issue_id' => $draft->id,
            'user_id' => $user->id,
            'comment' => 'Draft comment body',
            'files' => [],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['mainBusinessID' => $this->businessId])
            ->postJson(route('issue.submit', $draft), [
                'title' => 'Submitted from draft',
                'priority' => Issue::PRIORITY_HIGH,
                'comment' => 'Final comment',
                'no_url' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('issues', [
            'id' => $draft->id,
            'status' => Issue::STATUS_PENDING,
            'title' => 'Submitted from draft',
        ]);
    }

    public function test_wrong_my_submit_path_returns_not_found(): void
    {
        $user = User::factory()->create();
        $draft = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => Issue::generateDraftIssueNumber(),
            'title' => 'Draft title',
            'status' => Issue::STATUS_DRAFT,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['mainBusinessID' => $this->businessId])
            ->postJson('/issue/my/'.$draft->id.'/submit', [
                'title' => 'Should fail',
                'priority' => Issue::PRIORITY_MEDIUM,
                'comment' => 'x',
                'no_url' => 1,
            ])
            ->assertNotFound();
    }

    public function test_issue_table_formats_thai_month_from_created_at(): void
    {
        $user = User::factory()->create();
        $issue = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => 'ABBL-IMS202608-000099',
            'title' => 'August issue',
            'status' => Issue::STATUS_PENDING,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => $user->id,
        ]);
        $issue->forceFill([
            'created_at' => '2026-08-03 10:00:00',
            'updated_at' => '2026-08-03 10:00:00',
        ])->saveQuietly();

        $response = $this->actingAs($user)
            ->withSession(['mainBusinessID' => $this->businessId])
            ->getJson('/issue/my/table?draw=1&start=0&length=10');

        $response->assertOk();

        $row = collect($response->json('data'))->firstWhere('id', $issue->id);
        $this->assertNotNull($row);
        $this->assertSame('03 สิงหาคม 2026', $row['created_at_formatted']);
    }

    public function test_save_draft_accepts_draft_issue_id_alias(): void
    {
        $user = User::factory()->create();
        $draft = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => Issue::generateDraftIssueNumber(),
            'title' => 'Old title',
            'status' => Issue::STATUS_DRAFT,
            'priority' => Issue::PRIORITY_LOW,
            'created_by' => $user->id,
        ]);

        IssueComment::create([
            'issue_id' => $draft->id,
            'user_id' => $user->id,
            'comment' => 'old',
            'files' => [],
        ]);

        $this->actingAs($user)
            ->withSession(['mainBusinessID' => $this->businessId])
            ->postJson(route('issue.draft.save'), [
                'draft_issue_id' => $draft->id,
                'title' => 'Updated draft title',
                'comment' => 'Updated comment',
                'priority' => Issue::PRIORITY_MEDIUM,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('issue_id', $draft->id);

        $this->assertDatabaseHas('issues', [
            'id' => $draft->id,
            'title' => 'Updated draft title',
            'status' => Issue::STATUS_DRAFT,
        ]);
    }
}
