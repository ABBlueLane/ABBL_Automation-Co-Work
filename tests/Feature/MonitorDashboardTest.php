<?php

namespace Tests\Feature;

use App\Models\MonitorIncident;
use App\Models\MonitorTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_monitor_dashboard(): void
    {
        $this->get(route('monitor.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_load_status_and_summary(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $target = MonitorTarget::create([
            'name' => 'gateway-health',
            'url' => 'https://gateway.abgroup.co.th/up',
            'method' => 'GET',
            'interval_seconds' => 60,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'success_threshold' => 1,
            'expected_status' => [200],
            'is_active' => true,
        ]);

        MonitorIncident::create([
            'target_id' => $target->id,
            'started_at' => now()->subHour(),
            'ended_at' => now()->subMinutes(30),
            'duration_seconds' => 1800,
            'status' => MonitorIncident::STATUS_RESOLVED,
            'trigger_http_status' => 503,
            'checks_failed_count' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('monitor.status'))
            ->assertOk()
            ->assertJsonPath('targets.0.name', 'gateway-health')
            ->assertJsonStructure(['totals' => ['incidents_today', 'incidents_7d', 'incidents_month', 'downtime_seconds_7d']]);

        $this->actingAs($user)
            ->get(route('monitor.stats.summary', ['range' => '7d']))
            ->assertOk()
            ->assertJsonStructure(['uptime_percent', 'latency' => ['p50', 'p95', 'samples']]);
    }

    public function test_authenticated_user_can_open_settings_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('monitor.settings'))
            ->assertOk()
            ->assertSee('ตั้งค่าแจ้งเตือน')
            ->assertSee('LINE_CHANNEL_ACCESS_TOKEN');
    }

    public function test_internal_run_check_requires_secret(): void
    {
        config()->set('monitor.internal_run_secret', 'test-secret');
        config()->set('monitor.targets', []);

        $this->postJson(route('monitor.internal.run-check'))
            ->assertUnauthorized();

        $this->postJson(route('monitor.internal.run-check'), [], [
            'X-Monitor-Secret' => 'test-secret',
        ])->assertOk()->assertJsonPath('ok', true);
    }
}
