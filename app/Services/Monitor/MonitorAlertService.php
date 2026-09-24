<?php

namespace App\Services\Monitor;

use App\Models\MonitorIncident;
use App\Models\MonitorTarget;
use App\Services\Line\LineMessagingClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MonitorAlertService
{
    public function __construct(
        private readonly LineMessagingClient $lineMessagingClient,
        private readonly MonitorSettingsService $settings,
    ) {}

    public function notifyDown(MonitorTarget $target, MonitorIncident $incident): bool
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $startedAt = $incident->started_at?->setTimezone($timezone)->format('Y-m-d H:i').' '.$timezone;
        $cause = $incident->trigger_http_status
            ? 'HTTP '.$incident->trigger_http_status
            : ($incident->trigger_error ?: 'unknown');

        $message = implode("\n", [
            '[DOWN] '.$target->name,
            'ตั้งแต่: '.$startedAt,
            'สาเหตุ: '.$cause,
            'URL: '.$target->url,
        ]);

        return $this->dispatch('DOWN', $target->name, $message);
    }

    public function notifyRecovered(MonitorTarget $target, MonitorIncident $incident): bool
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $startedAt = $incident->started_at?->setTimezone($timezone)->format('H:i');
        $endedAt = $incident->ended_at?->setTimezone($timezone)->format('H:i');
        $durationMinutes = (int) ceil(max(0, (int) ($incident->duration_seconds ?? 0)) / 60);
        $dateLabel = $incident->started_at?->setTimezone($timezone)->format('Y-m-d') ?? '';

        $message = implode("\n", [
            '[RECOVERED] '.$target->name,
            'Downtime: '.$durationMinutes.' นาที',
            'ช่วง: '.$startedAt.' – '.$endedAt.' '.$timezone,
            'วันที่: '.$dateLabel,
            'URL: '.$target->url,
        ]);

        return $this->dispatch('RECOVERED', $target->name, $message);
    }

    public function sendTestMessage(?string $lineTo = null): array
    {
        $to = $lineTo ?: $this->settings->lineGroupSourceId();

        if (! $this->settings->lineTokenConfigured()) {
            return ['ok' => false, 'message' => 'ยังไม่ได้ตั้ง LINE_CHANNEL_ACCESS_TOKEN ใน .env'];
        }

        if ($to === null || $to === '') {
            return ['ok' => false, 'message' => 'ยังไม่ได้เลือกกลุ่ม LINE'];
        }

        $text = $this->buildLatestStatusMessage();

        try {
            $sent = $this->lineMessagingClient->pushText($to, $text);

            return $sent
                ? ['ok' => true, 'message' => 'ส่งสถานะ monitor ล่าสุดเข้ากลุ่มแล้ว']
                : ['ok' => false, 'message' => 'ส่งไม่สำเร็จ — ตรวจว่า OA ยังอยู่ในกลุ่มและ token ถูกต้อง'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'ส่งไม่สำเร็จ: '.$e->getMessage()];
        }
    }

    public function buildLatestStatusMessage(): string
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $latencyThreshold = (int) config('monitor.latency_threshold_ms', 10000);
        $now = now()->timezone($timezone)->format('Y-m-d H:i:s').' '.$timezone;

        $targets = MonitorTarget::query()->orderBy('id')->get();
        $lines = [
            '[STATUS] Uptime Monitor',
            'เวลา: '.$now,
            '',
        ];

        if ($targets->isEmpty()) {
            $lines[] = 'ยังไม่มี target ในระบบ';

            return implode("\n", $lines);
        }

        $overall = 'UP';

        foreach ($targets as $target) {
            $latestCheck = $target->checks()
                ->orderByDesc('checked_at')
                ->orderByDesc('id')
                ->first();

            $openIncident = $target->incidents()
                ->where('status', MonitorIncident::STATUS_OPEN)
                ->latest('started_at')
                ->first();

            if ($openIncident) {
                $status = 'DOWN';
                $overall = 'DOWN';
            } elseif (! $target->is_active) {
                $status = 'INACTIVE';
            } elseif (! $latestCheck) {
                $status = 'UNKNOWN';
                if ($overall === 'UP') {
                    $overall = 'UNKNOWN';
                }
            } elseif (! $latestCheck->ok) {
                $status = 'DEGRADED';
                if ($overall === 'UP') {
                    $overall = 'DEGRADED';
                }
            } elseif ($latencyThreshold > 0 && (int) $latestCheck->latency_ms >= $latencyThreshold) {
                $status = 'DEGRADED';
                if ($overall === 'UP') {
                    $overall = 'DEGRADED';
                }
            } else {
                $status = 'UP';
            }

            $checkedAt = $latestCheck?->checked_at?->setTimezone($timezone)->format('H:i:s') ?? '-';
            $http = $latestCheck?->http_status ?? '-';
            $latency = $latestCheck?->latency_ms !== null ? $latestCheck->latency_ms.'ms' : '-';
            $active = $target->is_active ? '' : ' (off)';

            $lines[] = sprintf(
                '• %s%s: %s | HTTP %s | %s | %s',
                $target->name,
                $active,
                $status,
                $http,
                $latency,
                $checkedAt
            );

            if ($openIncident) {
                $started = $openIncident->started_at?->setTimezone($timezone)->format('Y-m-d H:i') ?? '-';
                $cause = $openIncident->trigger_http_status
                    ? 'HTTP '.$openIncident->trigger_http_status
                    : ($openIncident->trigger_error ?: 'unknown');
                $lines[] = '  ล่มตั้งแต่ '.$started.' ('.$cause.')';
            }
        }

        array_splice($lines, 2, 0, 'ภาพรวม: '.$overall);

        return implode("\n", $lines);
    }

    private function dispatch(string $event, string $targetName, string $message): bool
    {
        if (! $this->settings->alertsEnabled()) {
            Log::info('Monitor alert skipped (disabled).', [
                'event' => $event,
                'target' => $targetName,
            ]);

            return true;
        }

        $lineTo = $this->settings->lineGroupSourceId() ?? '';
        /** @var list<string> $mailTo */
        $mailTo = config('monitor.alerts.mail_to', []);

        $channelsConfigured = $lineTo !== '' || count($mailTo) > 0;

        if (! $channelsConfigured) {
            Log::warning('Monitor alert has no channel configured; logged only.', [
                'event' => $event,
                'target' => $targetName,
                'message' => $message,
            ]);

            return true;
        }

        $sent = false;

        if ($lineTo !== '') {
            if (! $this->settings->lineTokenConfigured()) {
                Log::warning('Monitor LINE alert skipped: missing channel access token.', [
                    'event' => $event,
                    'target' => $targetName,
                ]);
            } else {
                try {
                    if ($this->lineMessagingClient->pushText($lineTo, $message)) {
                        $sent = true;
                    }
                } catch (Throwable $e) {
                    Log::warning('Monitor LINE alert failed.', [
                        'event' => $event,
                        'target' => $targetName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (count($mailTo) > 0) {
            try {
                Mail::raw($message, function ($mail) use ($mailTo, $event, $targetName): void {
                    $mail->to($mailTo)
                        ->subject('[Uptime Monitor] '.$event.' '.$targetName);
                });
                $sent = true;
            } catch (Throwable $e) {
                Log::warning('Monitor email alert failed.', [
                    'event' => $event,
                    'target' => $targetName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $sent) {
            Log::error('Monitor alert failed on all configured channels.', [
                'event' => $event,
                'target' => $targetName,
            ]);
        }

        return $sent;
    }
}
