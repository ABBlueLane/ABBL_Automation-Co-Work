<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_issue_comments_for_modal(): void
    {
        $user = User::factory()->create();
        $business = Business::unguarded(function () {
            return Business::create([
                'business_type' => 1,
                'business_vat_status' => 1,
                'business_branch_status' => 1,
                'business_account_finance_year' => 12,
                'business_business_finance_year' => 12,
                'business_name' => 'Test Business',
                'business_code' => 'TBIZ',
                'business_status' => 1,
                'allow_issue' => 1,
            ]);
        });

        $issue = Issue::create([
            'business_id' => $business->id,
            'issue_number' => 'TBIZ-IMS-000001',
            'title' => 'Issue title',
            'status' => Issue::STATUS_PENDING,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => $user->id,
        ]);

        IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'comment' => 'ข้อความทดสอบสำหรับ modal',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('issue.comments.index', [$business->id, $issue->id]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonFragment(['comment' => 'ข้อความทดสอบสำหรับ modal']);
    }
}
