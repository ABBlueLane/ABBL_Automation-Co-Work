<?php

namespace Tests\Unit\Services\Monitor;

use App\Models\MonitorCheck;
use App\Models\MonitorIncident;
use App\Models\MonitorTarget;
use App\Services\Line\LineMessagingClient;
use App\Services\Monitor\MonitorAlertService;
use App\Services\Monitor\MonitorCheckService;
use App\Services\Monitor\MonitorRetentionService;
use App\Services\Monitor\MonitorSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class MonitorCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opens_incident_after_failure_threshold_and_sends_down_alert(): void
    {
        Mail::fake();
        config()->set('monitor.alerts.enabled', true);
        config()->set('monitor.alerts.line_to', '');
        config()->set('monitor.alerts.mail_to', ['ops@example.com']);
        config()->set('monitor.targets', []);

        $target = MonitorTarget::create([
            'name' => 'gateway-health',
            'url' => 'https://gateway.example.test/up',
            'method' => 'GET',
            'interval_seconds' => 60,
            'timeout_seconds' => 5,
            'failure_threshold' => 2,
            'success_threshold' => 1,
            'expected_status' => [200],
            'is_active' => true,
        ]);

        Http::fake([
            'https://gateway.example.test/up' => Http::sequence()
                ->push('fail', 503)
                ->push('fail', 503)
                ->push('ok', 200),
        ]);

        $service = app(MonitorCheckService::class);

        $service->runSingleCheck($target->fresh());
        $this->assertSame(0, MonitorIncident::count());

        $service->runSingleCheck($target->fresh());
        $this->assertSame(1, MonitorIncident::count());
        $incident = MonitorIncident::first();
        $this->assertSame(MonitorIncident::STATUS_OPEN, $incident->status);
        $this->assertNotNull($incident->notified_down_at);

        $service->runSingleCheck($target->fresh());
        $incident->refresh();
        $this->assertSame(MonitorIncident::STATUS_RESOLVED, $incident->status);
        $this->assertNotNull($incident->notified_recovered_at);
        $this->assertNotNull($incident->ended_at);
        $this->assertNotNull($incident->duration_seconds);
    }

    public function test_does_not_open_incident_before_threshold(): void
    {
        config()->set('monitor.alerts.enabled', false);
        config()->set('monitor.targets', []);

        $target = MonitorTarget::create([
            'name' => 'gateway-login',
            'url' => 'https://gateway.example.test/',
            'method' => 'GET',
            'interval_seconds' => 60,
            'timeout_seconds' => 5,
            'failure_threshold' => 3,
            'success_threshold' => 1,
            'expected_status' => [200],
            'is_active' => true,
        ]);

        Http::fake([
            'https://gateway.example.test/' => Http::response('fail', 503),
        ]);

        $service = app(MonitorCheckService::class);
        $service->runSingleCheck($target);
        $service->runSingleCheck($target->fresh());

        $this->assertSame(0, MonitorIncident::count());
        $this->assertSame('degraded', $target->fresh()->last_status);
        $this->assertSame(2, MonitorCheck::count());
    }

    public function test_retention_purges_old_checks_only(): void
    {
        $target = MonitorTarget::create([
            'name' => 'gateway-health',
            'url' => 'https://gateway.example.test/up',
            'method' => 'GET',
            'interval_seconds' => 60,
            'timeout_seconds' => 5,
            'failure_threshold' => 2,
            'success_threshold' => 1,
            'expected_status' => [200],
            'is_active' => true,
        ]);

        MonitorCheck::create([
            'target_id' => $target->id,
            'checked_at' => now()->subDays(100),
            'ok' => true,
            'http_status' => 200,
            'latency_ms' => 10,
            'probe_host' => 'test',
        ]);
        MonitorCheck::create([
            'target_id' => $target->id,
            'checked_at' => now()->subDays(10),
            'ok' => true,
            'http_status' => 200,
            'latency_ms' => 12,
            'probe_host' => 'test',
        ]);

        $deleted = app(MonitorRetentionService::class)->purgeExpiredChecks(90);

        $this->assertSame(1, $deleted);
        $this->assertSame(1, MonitorCheck::count());
    }

    public function test_status_message_includes_latest_target_checks(): void
    {
        config()->set('services.line.channel_access_token', 'test-token');
        config()->set('monitor.alerts.mail_to', []);

        app(MonitorSettingsService::class)->save([
            'alerts_enabled' => true,
            'line_chat_source_id' => 'Cgroup1',
        ]);

        $target = MonitorTarget::create([
            'name' => 'gateway-health',
            'url' => 'https://gateway.example.test/up',
            'method' => 'GET',
            'interval_seconds' => 60,
            'timeout_seconds' => 5,
            'failure_threshold' => 2,
            'success_threshold' => 1,
            'expected_status' => [200],
            'is_active' => true,
        ]);

        MonitorCheck::create([
            'target_id' => $target->id,
            'checked_at' => now(),
            'ok' => true,
            'http_status' => 200,
            'latency_ms' => 123,
            'probe_host' => 'test',
        ]);

        $line = Mockery::mock(LineMessagingClient::class);
        $line->shouldReceive('pushText')
            ->once()
            ->withArgs(function (string $to, string $text): bool {
                return $to === 'Cgroup1'
                    && str_contains($text, 'รายงานสถานะ Gateway')
                    && str_contains($text, 'การทำงานของระบบ')
                    && str_contains($text, 'ใช้งานได้ปกติ')
                    && str_contains($text, 'ผลการตอบกลับของระบบ');
            })
            ->andReturn(true);
        $this->app->instance(LineMessagingClient::class, $line);

        $result = app(MonitorAlertService::class)->sendTestMessage('Cgroup1');
        $this->assertTrue($result['ok']);
    }

    public function test_alert_service_pushes_line_message(): void
    {
        config()->set('services.line.channel_access_token', 'test-token');

        app(MonitorSettingsService::class)->save([
            'alerts_enabled' => true,
            'line_chat_source_id' => 'U123',
        ]);

        $line = Mockery::mock(LineMessagingClient::class);
        $line->shouldReceive('pushText')
            ->once()
            ->withArgs(fn (string $to, string $text) => $to === 'U123' && str_contains($text, 'ระบบมีปัญหา'))
            ->andReturn(true);

        $this->app->instance(LineMessagingClient::class, $line);

        $target = new MonitorTarget([
            'name' => 'gateway-health',
            'url' => 'https://gateway.abgroup.co.th/up',
        ]);
        $incident = new MonitorIncident([
            'started_at' => now(),
            'trigger_http_status' => 503,
            'trigger_error' => null,
        ]);

        $ok = app(MonitorAlertService::class)->notifyDown($target, $incident);
        $this->assertTrue($ok);
    }
}
