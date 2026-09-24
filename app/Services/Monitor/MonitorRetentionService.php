<?php

namespace App\Services\Monitor;

use App\Models\MonitorCheck;
use Illuminate\Support\Facades\Log;

class MonitorRetentionService
{
    public function purgeExpiredChecks(?int $retentionDays = null): int
    {
        $days = $retentionDays ?? (int) config('monitor.checks_retention_days', 90);

        if ($days <= 0) {
            Log::info('Monitor retention skipped (disabled).');

            return 0;
        }

        $cutoff = now()->subDays($days);

        $deleted = MonitorCheck::query()
            ->where('checked_at', '<', $cutoff)
            ->delete();

        Log::info('Monitor retention purged old checks.', [
            'retention_days' => $days,
            'cutoff' => $cutoff->toIso8601String(),
            'deleted' => $deleted,
        ]);

        return $deleted;
    }
}
