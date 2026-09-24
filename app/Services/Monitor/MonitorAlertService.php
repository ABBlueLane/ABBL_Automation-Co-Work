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
        $startedAt = $incident->started_at?->setTimezone($timezone)->format('d/m/Y H:i').' น.';
        $cause = $this->humanCause($incident->trigger_http_status, $incident->trigger_error);

        $message = $this->appendSuffix(implode("\n", [
            'แจ้งเตือน: ระบบมีปัญหา',
            'จุดตรวจ: '.$this->friendlyTargetName($target),
            'สถานะ: ใช้งานไม่ได้ในขณะนี้',
            'เริ่มมีปัญหาตั้งแต่: '.$startedAt,
            'สาเหตุโดยย่อ: '.$cause,
            'ลิงก์ที่ตรวจ: '.$target->url,
        ]));

        return $this->dispatch('DOWN', $target->name, $message);
    }

    public function notifyRecovered(MonitorTarget $target, MonitorIncident $incident): bool
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $startedAt = $incident->started_at?->setTimezone($timezone)->format('H:i').' น.';
        $endedAt = $incident->ended_at?->setTimezone($timezone)->format('H:i').' น.';
        $durationMinutes = (int) ceil(max(0, (int) ($incident->duration_seconds ?? 0)) / 60);
        $dateLabel = $incident->started_at?->setTimezone($timezone)->format('d/m/Y') ?? '';

        $message = $this->appendSuffix(implode("\n", [
            'แจ้งเตือน: ระบบกลับมาใช้งานได้แล้ว',
            'จุดตรวจ: '.$this->friendlyTargetName($target),
            'สถานะ: ใช้งานได้ปกติ',
            'ช่วงที่มีปัญหา: '.$startedAt.' – '.$endedAt.' ('.$dateLabel.')',
            'ระยะเวลาที่ล่ม: ประมาณ '.$durationMinutes.' นาที',
            'ลิงก์ที่ตรวจ: '.$target->url,
        ]));

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
                ? ['ok' => true, 'message' => 'ส่งสถานะล่าสุดเข้ากลุ่มแล้ว']
                : ['ok' => false, 'message' => 'ส่งไม่สำเร็จ — ตรวจว่า OA ยังอยู่ในกลุ่มและ token ถูกต้อง'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'ส่งไม่สำเร็จ: '.$e->getMessage()];
        }
    }

    public function buildLatestStatusMessage(): string
    {
        $timezone = config('monitor.timezone_display', 'Asia/Bangkok');
        $latencyThreshold = (int) config('monitor.latency_threshold_ms', 10000);
        $now = now()->timezone($timezone)->format('d/m/Y H:i').' น.';

        $targets = MonitorTarget::query()->orderBy('id')->get();
        $lines = [
            'รายงานสถานะเว็บ Gateway',
            'เวลา '.$now.' (เวลาประเทศไทย)',
            '',
        ];

        if ($targets->isEmpty()) {
            $lines[] = 'ยังไม่มีข้อมูลจุดตรวจในระบบ';

            return $this->appendSuffix(implode("\n", $lines));
        }

        $overall = 'up';
        $detailBlocks = [];

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
                $statusKey = 'down';
                $overall = 'down';
            } elseif (! $target->is_active) {
                $statusKey = 'inactive';
            } elseif (! $latestCheck) {
                $statusKey = 'unknown';
                if ($overall === 'up') {
                    $overall = 'unknown';
                }
            } elseif (! $latestCheck->ok) {
                $statusKey = 'degraded';
                if ($overall === 'up') {
                    $overall = 'degraded';
                }
            } elseif ($latencyThreshold > 0 && (int) $latestCheck->latency_ms >= $latencyThreshold) {
                $statusKey = 'degraded';
                if ($overall === 'up') {
                    $overall = 'degraded';
                }
            } else {
                $statusKey = 'up';
            }

            $checkedAt = $latestCheck?->checked_at?->setTimezone($timezone)->format('H:i').' น.' ?? 'ยังไม่เคยตรวจ';
            $latencyText = $latestCheck?->latency_ms !== null
                ? $this->formatLatencySeconds((int) $latestCheck->latency_ms)
                : '-';
            $responseText = $this->humanHttpResult($latestCheck?->http_status, $latestCheck?->ok);

            $block = [
                '• '.$this->friendlyTargetName($target).': '.$this->friendlyStatusLabel($statusKey),
                '  ผลตรวจ: '.$responseText,
                '  ความเร็วตอบกลับ: '.$latencyText,
                '  ตรวจล่าสุด: '.$checkedAt,
            ];

            if ($openIncident) {
                $started = $openIncident->started_at?->setTimezone($timezone)->format('d/m/Y H:i').' น.' ?? '-';
                $cause = $this->humanCause($openIncident->trigger_http_status, $openIncident->trigger_error);
                $block[] = '  มีปัญหาตั้งแต่: '.$started;
                $block[] = '  สาเหตุโดยย่อ: '.$cause;
            }

            $detailBlocks[] = implode("\n", $block);
        }

        $lines[] = 'สรุปตอนนี้: '.$this->friendlyOverallLabel($overall);
        $lines[] = '';
        $lines[] = implode("\n\n", $detailBlocks);

        return $this->appendSuffix(implode("\n", $lines));
    }

    private function appendSuffix(string $message): string
    {
        $suffix = $this->settings->statusMessageSuffix();
        if ($suffix === '') {
            return $message;
        }

        return $message."\n\n".$suffix;
    }

    private function friendlyTargetName(MonitorTarget $target): string
    {
        return match ($target->name) {
            'gateway-health' => 'จุดตรวจสุขภาพระบบ',
            'gateway-login' => 'หน้าเข้าสู่ระบบ',
            default => $target->name,
        };
    }

    private function friendlyStatusLabel(string $status): string
    {
        return match ($status) {
            'up' => 'ใช้งานได้ปกติ',
            'down' => 'ใช้งานไม่ได้',
            'degraded' => 'ช้าผิดปกติ / ควรเฝ้าระวัง',
            'inactive' => 'ปิดการตรวจชั่วคราว',
            default => 'ยังไม่ทราบสถานะ',
        };
    }

    private function friendlyOverallLabel(string $overall): string
    {
        return match ($overall) {
            'up' => 'ใช้งานได้ปกติ',
            'down' => 'มีจุดที่ใช้งานไม่ได้',
            'degraded' => 'ยังใช้ได้ แต่มีจุดที่ช้าผิดปกติ',
            default => 'ยังสรุปไม่ได้ครบ',
        };
    }

    private function humanHttpResult(?int $httpStatus, ?bool $ok): string
    {
        if ($httpStatus === null) {
            return 'เชื่อมต่อไม่สำเร็จ';
        }

        if ($ok) {
            return 'ตอบกลับสำเร็จ';
        }

        return 'ตอบกลับผิดปกติ (รหัส '.$httpStatus.')';
    }

    private function humanCause(?int $httpStatus, ?string $error): string
    {
        if ($httpStatus !== null) {
            return match (true) {
                $httpStatus === 503 => 'เซิร์ฟเวอร์ไม่พร้อมให้บริการชั่วคราว (503)',
                $httpStatus >= 500 => 'เซิร์ฟเวอร์มีข้อผิดพลาดภายใน (รหัส '.$httpStatus.')',
                $httpStatus >= 400 => 'คำขอถูกปฏิเสธ (รหัส '.$httpStatus.')',
                default => 'รหัสตอบกลับ '.$httpStatus,
            };
        }

        if (is_string($error) && $error !== '') {
            if (str_contains(strtolower($error), 'timed out') || str_contains($error, 'timeout')) {
                return 'รอคำตอบนานเกินไป (timeout)';
            }

            return $error;
        }

        return 'ยังไม่ทราบสาเหตุชัดเจน';
    }

    private function formatLatencySeconds(int $latencyMs): string
    {
        if ($latencyMs < 1000) {
            return $latencyMs.' มิลลิวินาที';
        }

        return number_format($latencyMs / 1000, 1).' วินาที';
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
