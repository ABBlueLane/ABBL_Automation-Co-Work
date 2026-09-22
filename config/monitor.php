<?php

return [
    'timezone_display' => env('MONITOR_TIMEZONE_DISPLAY', 'Asia/Bangkok'),

    'targets' => [
        [
            'name' => 'gateway-health',
            'url' => 'https://gateway.abgroup.co.th/up',
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
            'url' => 'https://gateway.abgroup.co.th/',
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
