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

    private function dispatch(string $event, string $targetName, string $message): bool
    {
        if (! (bool) config('monitor.alerts.enabled', true)) {
            Log::info('Monitor alert skipped (disabled).', [
                'event' => $event,
                'target' => $targetName,
            ]);

            return true;
        }

        $lineTo = trim((string) config('monitor.alerts.line_to', ''));
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
