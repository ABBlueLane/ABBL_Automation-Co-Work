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

        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $text = implode("\n", [
            '[TEST] Uptime Monitor',
            'ทดสอบส่งเข้ากลุ่มนี้สำเร็จ',
            'เวลา: '.now()->timezone($timezone)->format('Y-m-d H:i:s').' '.$timezone,
        ]);

        try {
            $sent = $this->lineMessagingClient->pushText($to, $text);

            return $sent
                ? ['ok' => true, 'message' => 'ส่งข้อความทดสอบเข้ากลุ่มแล้ว']
                : ['ok' => false, 'message' => 'ส่งไม่สำเร็จ — ตรวจว่า OA ยังอยู่ในกลุ่มและ token ถูกต้อง'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'ส่งไม่สำเร็จ: '.$e->getMessage()];
        }
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
