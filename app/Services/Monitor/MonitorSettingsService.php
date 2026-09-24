<?php

namespace App\Services\Monitor;

use App\Models\LineChatSource;
use App\Models\MonitorSetting;
use App\Services\Line\LineMessagingClient;
use Illuminate\Support\Collection;

class MonitorSettingsService
{
    public const KEY_ALERTS_ENABLED = 'alerts_enabled';

    public const KEY_LINE_CHAT_SOURCE_ID = 'line_chat_source_id';

    public const KEY_STATUS_MESSAGE_SUFFIX = 'status_message_suffix';

    public function __construct(
        private readonly LineMessagingClient $lineMessagingClient,
    ) {}

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

    public function statusMessageSuffix(): string
    {
        return trim((string) ($this->get(self::KEY_STATUS_MESSAGE_SUFFIX) ?? ''));
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
    public function availableLineGroups(bool $refreshNames = false): Collection
    {
        if ($refreshNames) {
            $this->refreshGroupDisplayNames();
        }

        return LineChatSource::query()
            ->where('source_type', 'group')
            ->orderByRaw('CASE WHEN display_name IS NULL OR display_name = ? THEN 1 ELSE 0 END', [''])
            ->orderBy('display_name')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Pull groupName from LINE for groups missing a display_name (or force all).
     */
    public function refreshGroupDisplayNames(bool $force = false): int
    {
        if (! $this->lineTokenConfigured()) {
            return 0;
        }

        $query = LineChatSource::query()->where('source_type', 'group');
        if (! $force) {
            $query->where(function ($q): void {
                $q->whereNull('display_name')->orWhere('display_name', '');
            });
        }

        $updated = 0;

        foreach ($query->orderBy('id')->get() as $group) {
            $summary = $this->lineMessagingClient->getGroupSummary($group->source_id);
            $name = is_array($summary) ? ($summary['groupName'] ?? null) : null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            if ($group->display_name === $name) {
                continue;
            }

            $group->display_name = $name;
            $group->save();
            $updated++;
        }

        return $updated;
    }

    public function lineTokenConfigured(): bool
    {
        $token = config('services.line.channel_access_token');

        return is_string($token) && $token !== '';
    }

    public function botDisplayName(): ?string
    {
        $info = $this->lineMessagingClient->getBotInfo();
        $name = is_array($info) ? ($info['displayName'] ?? null) : null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * @param  array{alerts_enabled?: bool, line_chat_source_id?: string|null, status_message_suffix?: string|null}  $data
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

        if (array_key_exists('status_message_suffix', $data)) {
            $suffix = $data['status_message_suffix'];
            $this->put(
                self::KEY_STATUS_MESSAGE_SUFFIX,
                is_string($suffix) && trim($suffix) !== '' ? trim($suffix) : null,
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
