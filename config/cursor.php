<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IMS → Cursor CLI autofix
    |--------------------------------------------------------------------------
    |
    | When an IMS issue becomes pending, the system can invoke Cursor CLI
    | (headless `agent -p --force`) against a mapped repository based on the
    | issue URL host (e.g. gateway.co.th → AB_Gateway).
    |
    */

    'autofix' => [
        'enabled' => (bool) env('CURSOR_AUTOFIX_ENABLED', false),
        'dry_run' => (bool) env('CURSOR_AUTOFIX_DRY_RUN', true),
        'create_branch' => (bool) env('CURSOR_AUTOFIX_CREATE_BRANCH', true),
        'post_comment' => (bool) env('CURSOR_AUTOFIX_POST_COMMENT', true),
        'timeout_seconds' => (int) env('CURSOR_CLI_TIMEOUT', 900),
        'system_user_id' => env('CURSOR_AUTOFIX_SYSTEM_USER_ID', env('LINE_IMS_SYSTEM_USER_ID')),
    ],

    'cli' => [
        // Official Cursor Agent CLI binary (`agent` after `curl https://cursor.com/install | bash`)
        'binary' => env('CURSOR_CLI_BINARY', 'agent'),
        'api_key' => env('CURSOR_API_KEY'),
        'model' => env('CURSOR_CLI_MODEL'),
        'force' => (bool) env('CURSOR_CLI_FORCE', true),
        'output_format' => env('CURSOR_CLI_OUTPUT_FORMAT', 'text'),
        'probe_timeout' => (int) env('CURSOR_CLI_PROBE_TIMEOUT', 60),
        'chat_timeout' => (int) env('CURSOR_CLI_CHAT_TIMEOUT', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository root + mappings
    |--------------------------------------------------------------------------
    |
    | Each mapping matches an issue URL (host / contains) to a local checkout.
    | Path may be absolute, or relative to `repos_root`.
    |
    */

    'repos_root' => env('CURSOR_REPOS_PATH', '/var/www/repos'),

    'repos' => [
        [
            'key' => 'AB_Gateway',
            'name' => 'AB Gateway',
            'path' => env('CURSOR_REPO_AB_GATEWAY_PATH', 'AB_Gateway'),
            'hosts' => [
                'gateway.co.th',
                'www.gateway.co.th',
            ],
            'url_contains' => [
                'gateway.co.th',
            ],
        ],
        // Add more mappings as needed, e.g.:
        // [
        //     'key' => 'ABBL_Automation',
        //     'name' => 'ABBL Automation Co-Work',
        //     'path' => 'ABBL_Automation-Co-Work',
        //     'hosts' => ['ims.example.com'],
        //     'url_contains' => [],
        // ],
    ],

];
