<?php

use Hashcrypttech\HashGuardian\Watchers;

return [

    /*
    |--------------------------------------------------------------------------
    | HashGuardian Master Switch
    |--------------------------------------------------------------------------
    |
    | Globally enable or disable all monitoring. When disabled, no events
    | are recorded regardless of individual watcher settings.
    |
    */
    'enabled' => env('HASHGUARDIAN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HashGuardian Dashboard Path
    |--------------------------------------------------------------------------
    |
    | The URI prefix for the HashGuardian dashboard. All dashboard routes will be
    | registered under this path (e.g., /hashguardian/requests).
    |
    */
    'path' => env('HASHGUARDIAN_PATH', 'hashguardian'),

    /*
    |--------------------------------------------------------------------------
    | HashGuardian Dashboard Domain
    |--------------------------------------------------------------------------
    |
    | Optionally restrict the dashboard to a specific domain.
    |
    */
    'domain' => env('HASHGUARDIAN_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the HashGuardian dashboard routes.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Dark Mode
    |--------------------------------------------------------------------------
    */
    'dark_mode' => env('HASHGUARDIAN_DARK_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'database' => [
            'connection' => env('HASHGUARDIAN_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Pruning
    |--------------------------------------------------------------------------
    |
    | Entries older than this many hours will be pruned by hashguardian:prune.
    |
    */
    'prune' => [
        'hours' => env('HASHGUARDIAN_PRUNE_HOURS', 72),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling Rates
    |--------------------------------------------------------------------------
    |
    | Control what percentage of entry points are recorded (0.0 to 1.0).
    | Child events inherit the sampling decision of their parent.
    |
    */
    'sampling' => [
        'requests' => (float) env('HASHGUARDIAN_REQUEST_SAMPLE_RATE', 1.0),
        'commands' => (float) env('HASHGUARDIAN_COMMAND_SAMPLE_RATE', 1.0),
        'exceptions' => (float) env('HASHGUARDIAN_EXCEPTION_SAMPLE_RATE', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Paths
    |--------------------------------------------------------------------------
    |
    | Requests matching these patterns will never be recorded.
    |
    */
    'ignore_paths' => [
        'hashguardian*',
        'telescope*',
        'pulse*',
        '_debugbar*',
        'horizon*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Commands
    |--------------------------------------------------------------------------
    |
    | Artisan commands that should not be recorded.
    |
    */
    'ignore_commands' => [
        'schedule:run',
        'schedule:finish',
        'hashguardian:prune',
        'hashguardian:clear',
        'hashguardian:monitor',
        'package:discover',
        'vendor:publish',
    ],

    /*
    |--------------------------------------------------------------------------
    | Aggregated Analytics
    |--------------------------------------------------------------------------
    |
    | Enable metric aggregation for trends and analytics. Raw entries are
    | aggregated hourly, then rolled up into daily/weekly/monthly summaries.
    | Run `hashguardian:aggregate` on schedule to build aggregates.
    |
    */
    'aggregates' => [
        'enabled' => env('HASHGUARDIAN_AGGREGATES_ENABLED', true),
        'retention_days' => env('HASHGUARDIAN_RETENTION_DAYS', 30),
        'aggregate_retention_days' => env('HASHGUARDIAN_AGGREGATE_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the REST API for programmatic access to HashGuardian data.
    | Requires API tokens created via `hashguardian:token create`.
    |
    */
    'api' => [
        'enabled' => env('HASHGUARDIAN_API_ENABLED', false),
        'prefix' => 'api/v1',
        'rate_limit' => env('HASHGUARDIAN_API_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure server resource monitoring. Requires running the
    | hashguardian:monitor command as a long-running process.
    |
    */
    'server_monitoring' => [
        'enabled' => env('HASHGUARDIAN_SERVER_MONITORING', false),
        'interval' => (int) env('HASHGUARDIAN_METRICS_INTERVAL', 15),
        'disk_mount' => env('HASHGUARDIAN_DISK_MOUNT', '/'),
        'fpm_status_url' => env('HASHGUARDIAN_FPM_STATUS_URL', null),
        'prune_hours' => (int) env('HASHGUARDIAN_METRICS_PRUNE_HOURS', 168), // 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Metrics
    |--------------------------------------------------------------------------
    |
    | Enable custom metrics recording via HashGuardian::count(), HashGuardian::metric(),
    | and timer methods.
    |
    */
    'custom_metrics' => [
        'enabled' => env('HASHGUARDIAN_CUSTOM_METRICS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    |
    | Configure scheduled digest reports via email.
    |
    */
    'reports' => [
        'enabled' => env('HASHGUARDIAN_REPORTS_ENABLED', false),
        'schedule' => 'daily',
        'time' => '08:00',
        'recipients' => env('HASHGUARDIAN_REPORT_RECIPIENTS', ''),
        'include' => ['requests', 'exceptions', 'jobs', 'server', 'activity'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    |
    | Configure export functionality for entries.
    |
    */
    'export' => [
        'max_rows' => 10000,
        'storage_disk' => env('HASHGUARDIAN_EXPORT_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Watchers
    |--------------------------------------------------------------------------
    |
    | Each watcher can be enabled/disabled independently with custom options.
    |
    */
    'watchers' => [

        Watchers\RequestWatcher::class => [
            'enabled' => env('HASHGUARDIAN_REQUEST_WATCHER', true),
            'size_limit' => 64, // KB limit for request/response payloads
            'ignore_status_codes' => [],
        ],

        Watchers\QueryWatcher::class => [
            'enabled' => env('HASHGUARDIAN_QUERY_WATCHER', true),
            'slow' => 100, // Queries slower than this (ms) are flagged
            'ignore_packages' => true,
        ],

        Watchers\ExceptionWatcher::class => [
            'enabled' => env('HASHGUARDIAN_EXCEPTION_WATCHER', true),
            'capture_source_code' => true,
            'source_code_lines' => 10,
        ],

        Watchers\JobWatcher::class => [
            'enabled' => env('HASHGUARDIAN_JOB_WATCHER', true),
        ],

        Watchers\OutgoingRequestWatcher::class => [
            'enabled' => env('HASHGUARDIAN_OUTGOING_REQUEST_WATCHER', true),
            'size_limit' => 64,
        ],

        Watchers\CacheWatcher::class => [
            'enabled' => env('HASHGUARDIAN_CACHE_WATCHER', true),
            'ignore_keys' => [
                'illuminate:*',
                'laravel_cache*',
            ],
        ],

        Watchers\MailWatcher::class => [
            'enabled' => env('HASHGUARDIAN_MAIL_WATCHER', true),
        ],

        Watchers\NotificationWatcher::class => [
            'enabled' => env('HASHGUARDIAN_NOTIFICATION_WATCHER', true),
        ],

        Watchers\CommandWatcher::class => [
            'enabled' => env('HASHGUARDIAN_COMMAND_WATCHER', true),
        ],

        Watchers\ScheduleWatcher::class => [
            'enabled' => env('HASHGUARDIAN_SCHEDULE_WATCHER', true),
        ],

        Watchers\LogWatcher::class => [
            'enabled' => env('HASHGUARDIAN_LOG_WATCHER', true),
            'level' => env('HASHGUARDIAN_LOG_LEVEL', 'error'),
        ],

        Watchers\ActivityWatcher::class => [
            'enabled' => env('HASHGUARDIAN_ACTIVITY_WATCHER', true),
            'ignore_paths' => ['/hashguardian*', '/_debugbar*', '/sanctum*'],
            'ignore_methods' => ['HEAD', 'OPTIONS'],
            'track_guests' => false,
            'record_payload' => false,
        ],

        Watchers\BatchWatcher::class => [
            'enabled' => env('HASHGUARDIAN_BATCH_WATCHER', true),
        ],

        Watchers\DumpWatcher::class => [
            'enabled' => env('HASHGUARDIAN_DUMP_WATCHER', true),
            'always' => env('HASHGUARDIAN_DUMP_ALWAYS', false),
        ],

        Watchers\EventWatcher::class => [
            'enabled' => env('HASHGUARDIAN_EVENT_WATCHER', true),
            'ignore_frameworks' => true,
            'ignore' => [],
        ],

        Watchers\GateWatcher::class => [
            'enabled' => env('HASHGUARDIAN_GATE_WATCHER', true),
            'ignore_abilities' => [],
        ],

        Watchers\ModelWatcher::class => [
            'enabled' => env('HASHGUARDIAN_MODEL_WATCHER', true),
            'events' => 'eloquent.*',
            'hydrations' => false,
            'ignore' => [],
        ],

        Watchers\RedisWatcher::class => [
            'enabled' => env('HASHGUARDIAN_REDIS_WATCHER', true),
        ],

        Watchers\ViewWatcher::class => [
            'enabled' => env('HASHGUARDIAN_VIEW_WATCHER', true),
            'events' => 'composing:*',
            'ignore' => [
                'hashguardian::*',
            ],
        ],

    ],
];
