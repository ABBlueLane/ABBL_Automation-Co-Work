<?php

namespace Tests\Feature;

use App\Jobs\ProcessImsCursorAutofix;
use App\Models\Business;
use App\Models\CursorAutofixRun;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CursorAutofixUiTest extends TestCase
{
    use RefreshDatabase;

    private string $businessId = '9c9aafbc-f74a-4e30-b44a-1209b30431ad';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBusiness();
        $this->user = User::factory()->create(['id' => 1]);

        config()->set('cursor.autofix.enabled', true);
        config()->set('cursor.autofix.dry_run', true);
        config()->set('cursor.autofix.post_comment', false);
        config()->set('cursor.autofix.create_branch', false);
        config()->set('cursor.repos_root', sys_get_temp_dir().'/cursor-repos-ui');
        config()->set('cursor.repos', [
            [
                'key' => 'AB_Gateway',
                'name' => 'AB Gateway',
                'path' => 'AB_Gateway',
                'hosts' => ['gateway.co.th'],
                'url_contains' => ['gateway.co.th'],
            ],
        ]);
    }

    public function test_index_requires_auth(): void
    {
        $this->get(route('cursor_autofix.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_runs(): void
    {
        $issue = $this->createPendingIssue('https://gateway.co.th/x');
        CursorAutofixRun::create([
            'issue_id' => $issue->id,
            'status' => CursorAutofixRun::STATUS_SUCCEEDED,
            'repo_key' => 'AB_Gateway',
            'repo_name' => 'AB Gateway',
            'dry_run' => true,
            'finished_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('cursor_autofix.index'))
            ->assertOk()
            ->assertSee('Cursor Autofix')
            ->assertSee('AB_Gateway')
            ->assertSee($issue->issue_number);
    }

    public function test_show_page_displays_run_details(): void
    {
        $issue = $this->createPendingIssue('https://gateway.co.th/x');
        $run = CursorAutofixRun::create([
            'issue_id' => $issue->id,
            'status' => CursorAutofixRun::STATUS_SUCCEEDED,
            'repo_key' => 'AB_Gateway',
            'repo_name' => 'AB Gateway',
            'prompt' => 'fix gateway login',
            'stdout' => 'Dry run only',
            'dry_run' => true,
            'finished_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('cursor_autofix.show', $run))
            ->assertOk()
            ->assertSee('Autofix #'.$run->id)
            ->assertSee('fix gateway login')
            ->assertSee('Dry run only')
            ->assertSee($issue->issue_number);
    }

    public function test_store_queues_autofix_run(): void
    {
        Queue::fake();
        $issue = $this->createPendingIssue('https://gateway.co.th/bug');

        $response = $this->actingAs($this->user)->post(route('cursor_autofix.store'), [
            'issue_id' => $issue->id,
            'repo_key' => '',
        ]);

        $run = CursorAutofixRun::query()->where('issue_id', $issue->id)->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame(CursorAutofixRun::STATUS_QUEUED, $run->status);
        $this->assertSame('AB_Gateway', $run->repo_key);

        $response->assertRedirect(route('cursor_autofix.show', $run));
        Queue::assertPushed(ProcessImsCursorAutofix::class);
    }

    public function test_store_with_repo_override(): void
    {
        Queue::fake();
        $issue = $this->createPendingIssue(null);

        $response = $this->actingAs($this->user)->post(route('cursor_autofix.store'), [
            'issue_id' => $issue->id,
            'repo_key' => 'AB_Gateway',
        ]);

        $run = CursorAutofixRun::query()->where('issue_id', $issue->id)->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame('AB_Gateway', $run->repo_key);
        $response->assertRedirect(route('cursor_autofix.show', $run));
    }

    private function createPendingIssue(?string $url): Issue
    {
        return Issue::withoutEvents(function () use ($url) {
            $issue = Issue::create([
                'business_id' => $this->businessId,
                'issue_number' => 'ABBL-IMS202608-000010',
                'title' => 'UI autofix test',
                'url' => $url,
                'status' => Issue::STATUS_PENDING,
                'priority' => Issue::PRIORITY_MEDIUM,
                'created_by' => 1,
            ]);

            IssueComment::create([
                'issue_id' => $issue->id,
                'user_id' => 1,
                'comment' => 'รายละเอียดทดสอบ',
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
