<?php

namespace Tests\Unit\Services\Cursor;

use App\Jobs\ProcessImsCursorAutofix;
use App\Models\Business;
use App\Models\CursorAutofixRun;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use App\Services\Cursor\ImsCursorAutofixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImsCursorAutofixServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBusiness();
        User::factory()->create(['id' => 1]);

        config()->set('cursor.autofix.enabled', true);
        config()->set('cursor.autofix.dry_run', true);
        config()->set('cursor.autofix.post_comment', true);
        config()->set('cursor.autofix.system_user_id', 1);
        config()->set('cursor.autofix.create_branch', false);
        config()->set('cursor.repos_root', sys_get_temp_dir().'/cursor-repos-test');
        config()->set('cursor.repos', [
            [
                'key' => 'AB_Gateway',
                'name' => 'AB Gateway',
                'path' => 'AB_Gateway',
                'hosts' => ['gateway.co.th', 'www.gateway.co.th'],
                'url_contains' => ['gateway.co.th'],
            ],
        ]);
    }

    public function test_queue_for_issue_maps_gateway_url(): void
    {
        $issue = $this->createPendingIssue('https://gateway.co.th/broken');

        $run = app(ImsCursorAutofixService::class)->queueForIssue($issue);

        $this->assertNotNull($run);
        $this->assertSame(CursorAutofixRun::STATUS_QUEUED, $run->status);
        $this->assertSame('AB_Gateway', $run->repo_key);
        $this->assertStringContainsString('AB_Gateway', (string) $run->repo_path);
        $this->assertTrue($run->dry_run);
        $this->assertNotEmpty($run->prompt);
        $this->assertNotEmpty($run->command);
    }

    public function test_queue_skips_when_url_unmapped(): void
    {
        $issue = $this->createPendingIssue('https://unknown.example/x');

        $run = app(ImsCursorAutofixService::class)->queueForIssue($issue);

        $this->assertNotNull($run);
        $this->assertSame(CursorAutofixRun::STATUS_SKIPPED, $run->status);
    }

    public function test_repo_override(): void
    {
        $issue = $this->createPendingIssue(null);

        $run = app(ImsCursorAutofixService::class)->queueForIssue($issue, 'AB_Gateway');

        $this->assertSame('AB_Gateway', $run?->repo_key);
        $this->assertSame(CursorAutofixRun::STATUS_QUEUED, $run?->status);
    }

    public function test_dry_run_execute_succeeds_and_posts_comment(): void
    {
        $repoPath = config('cursor.repos_root').'/AB_Gateway';
        if (! is_dir($repoPath)) {
            mkdir($repoPath, 0777, true);
        }

        $issue = $this->createPendingIssue('https://gateway.co.th/bug');
        $service = app(ImsCursorAutofixService::class);
        $run = $service->queueForIssue($issue);
        $this->assertNotNull($run);

        $finished = $service->execute($run);

        $this->assertSame(CursorAutofixRun::STATUS_SUCCEEDED, $finished->status);
        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $issue->id,
            'user_id' => 1,
        ]);
        $this->assertTrue(
            IssueComment::query()
                ->where('issue_id', $issue->id)
                ->where('comment', 'like', '%[Cursor Autofix]%')
                ->exists()
        );
    }

    public function test_observer_dispatches_job_when_issue_becomes_pending(): void
    {
        Queue::fake();

        $issue = Issue::create([
            'business_id' => $this->businessId,
            'issue_number' => Issue::generateDraftIssueNumber(),
            'title' => 'gateway down',
            'url' => 'https://gateway.co.th/x',
            'status' => Issue::STATUS_DRAFT,
            'priority' => Issue::PRIORITY_MEDIUM,
            'created_by' => 1,
        ]);

        IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => 1,
            'comment' => 'detail',
            'files' => [],
        ]);

        $issue->update([
            'status' => Issue::STATUS_PENDING,
            'issue_number' => 'ABBL-IMS202608-000001',
        ]);

        Queue::assertPushed(ProcessImsCursorAutofix::class);
        $this->assertDatabaseHas('cursor_autofix_runs', [
            'issue_id' => $issue->id,
            'repo_key' => 'AB_Gateway',
            'status' => CursorAutofixRun::STATUS_QUEUED,
        ]);
    }

    private function createPendingIssue(?string $url): Issue
    {
        return Issue::withoutEvents(function () use ($url) {
            $issue = Issue::create([
                'business_id' => $this->businessId,
                'issue_number' => 'ABBL-IMS202608-000099',
                'title' => 'ปัญหา gateway',
                'url' => $url,
                'status' => Issue::STATUS_PENDING,
                'priority' => Issue::PRIORITY_HIGH,
                'created_by' => 1,
            ]);

            IssueComment::create([
                'issue_id' => $issue->id,
                'user_id' => 1,
                'comment' => 'login ไม่ได้ที่ gateway',
                'files' => [],
            ]);

            return $issue;
        });
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
