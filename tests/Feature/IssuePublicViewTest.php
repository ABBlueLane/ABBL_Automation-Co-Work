<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssuePublicViewTest extends TestCase
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

        User::factory()->create(['id' => 1]);
    }

    public function test_guest_can_view_submitted_issue_without_login(): void
    {
        $issue = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => 'ABBL-IMS202607-000004',
            'title' => 'ปัญหาจาก LINE',
            'status' => Issue::STATUS_PENDING,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => 1,
        ]);

        IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => 1,
            'comment' => 'รายละเอียดจาก LINE',
            'files' => [],
        ]);

        $this->get("/issue/{$this->businessId}/view/{$issue->id}")
            ->assertOk()
            ->assertSee('ปัญหาจาก LINE')
            ->assertSee('รายละเอียดจาก LINE');
    }
}
