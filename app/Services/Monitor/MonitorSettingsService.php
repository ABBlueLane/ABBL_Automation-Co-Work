<?php

namespace App\Services\Monitor;

use App\Models\LineChatSource;
use App\Models\MonitorSetting;
use Illuminate\Support\Collection;

class MonitorSettingsService
{
    public const KEY_ALERTS_ENABLED = 'alerts_enabled';

    public const KEY_LINE_CHAT_SOURCE_ID = 'line_chat_source_id';

    public function alertsEnabled(): bool
    {
        $stored = $this->get(self::KEY_ALERTS_ENABLED);

        if ($stored !== null) {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('monitor.alerts.enabled', false);
    }

    public function lineGroupSourceId(): ?string
    {
        $stored = $this->get(self::KEY_LINE_CHAT_SOURCE_ID);

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $fromEnv = trim((string) config('monitor.alerts.line_to', ''));

        return $fromEnv !== '' ? $fromEnv : null;
    }

    public function selectedLineGroup(): ?LineChatSource
    {
        $sourceId = $this->lineGroupSourceId();
        if ($sourceId === null) {
            return null;
        }

        return LineChatSource::query()
            ->where('source_type', 'group')
            ->where('source_id', $sourceId)
            ->first();
    }

    /**
     * Groups the LINE OA has already talked to (via webhook).
     *
     * @return Collection<int, LineChatSource>
     */
    public function availableLineGroups(): Collection
    {
        return LineChatSource::query()
            ->where('source_type', 'group')
            ->orderByDesc('updated_at')
            ->orderBy('display_name')
            ->get();
    }

    public function lineTokenConfigured(): bool
    {
        $token = config('services.line.channel_access_token');

        return is_string($token) && $token !== '';
    }

    /**
     * @param  array{alerts_enabled?: bool, line_chat_source_id?: string|null}  $data
     */
    public function save(array $data): void
    {
        if (array_key_exists('alerts_enabled', $data)) {
            $this->put(self::KEY_ALERTS_ENABLED, $data['alerts_enabled'] ? '1' : '0');
        }

        if (array_key_exists('line_chat_source_id', $data)) {
            $sourceId = $data['line_chat_source_id'];
            $this->put(
                self::KEY_LINE_CHAT_SOURCE_ID,
                is_string($sourceId) && $sourceId !== '' ? $sourceId : null,
            );
        }
    }

    public function get(string $key): ?string
    {
        $row = MonitorSetting::query()->find($key);

        return $row?->value;
    }

    public function put(string $key, ?string $value): void
    {
        MonitorSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
