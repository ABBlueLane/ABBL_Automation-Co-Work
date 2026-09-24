<?php

return [
    'timezone_display' => env('MONITOR_TIMEZONE_DISPLAY', 'Asia/Bangkok'),

    /*
    | Baseline date shown on the dashboard — history starts counting from this moment.
    | Leave null to use the earliest monitor_checks.checked_at (or "not started").
    */
    'baseline_started_at' => env('MONITOR_BASELINE_STARTED_AT'),

    /*
    | Optional latency threshold (ms). HTTP 2xx slower than this counts as degraded.
    */
    'latency_threshold_ms' => (int) env('MONITOR_LATENCY_THRESHOLD_MS', 10000),

    /*
    | Delete monitor_checks older than this many days (0 = disabled).
    */
    'checks_retention_days' => (int) env('MONITOR_CHECKS_RETENTION_DAYS', 90),

    /*
    | Shared secret for unauthenticated POST /api/internal/monitor/run-check
    | Leave empty to disable the internal endpoint.
    */
    'internal_run_secret' => env('MONITOR_INTERNAL_RUN_SECRET'),

    'alerts' => [
        'enabled' => (bool) env('MONITOR_ALERTS_ENABLED', true),
        'line_to' => env('MONITOR_LINE_TO'),
        'mail_to' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MONITOR_ALERT_MAIL_TO', ''))
        ))),
    ],

    'targets' => [
        [
            'name' => 'gateway-health',
            'url' => env('MONITOR_GATEWAY_HEALTH_URL', 'https://gateway.abgroup.co.th/up'),
            'method' => 'GET',
            'interval_seconds' => 60,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'success_threshold' => 1,
            'expected_status' => [200],
            'is_active' => true,
        ],
        [
            'name' => 'gateway-login',
            'url' => env('MONITOR_GATEWAY_LOGIN_URL', 'https://gateway.abgroup.co.th/'),
            'method' => 'GET',
            'interval_seconds' => 60,
            'timeout_seconds' => 15,
            'failure_threshold' => 3,
            'success_threshold' => 1,
            'expected_status' => [200],
            'is_active' => true,
        ],
    ],
];
