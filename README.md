# HashGuardian

Self-hosted monitoring and observability dashboard for Laravel applications. Track requests, queries, exceptions, jobs, and more — all within your own infrastructure.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require hashcrypttech/hashguardian
```

The service provider and facade are auto-discovered by Laravel. No manual registration is needed.

Run the install command to publish the config, migrations, and assets:

```bash
php artisan hashguardian:install
```

This will:
- Publish the `config/hashguardian.php` configuration file
- Publish the `HashGuardianServiceProvider` to `app/Providers/`
- Publish frontend assets to `public/vendor/hashguardian/`
- Run database migrations

## Configuration

After installation, configure HashGuardian via `config/hashguardian.php` or environment variables:

```env
HASHGUARDIAN_ENABLED=true
HASHGUARDIAN_PATH=hashguardian
HASHGUARDIAN_DB_CONNECTION=mysql
HASHGUARDIAN_PRUNE_HOURS=72
```

### Authorization

By default, the HashGuardian dashboard is only accessible in the `local` environment. To configure access in production, define the `viewHashGuardian` gate in your `App\Providers\HashGuardianServiceProvider`:

```php
use Hashcrypttech\HashGuardian\Facades\HashGuardian;

HashGuardian::auth(function ($request) {
    return in_array($request->user()?->email, [
        'admin@example.com',
    ]);
});
```

## Dashboard

Access the monitoring dashboard at:

```
https://your-app.com/hashguardian
```

## Watchers

HashGuardian includes 20 built-in watchers that can be individually enabled/disabled:

| Watcher | Description | Env Variable |
|---------|-------------|-------------|
| **RequestWatcher** | HTTP requests and response times | `HASHGUARDIAN_REQUEST_WATCHER` |
| **QueryWatcher** | Database queries with slow query detection | `HASHGUARDIAN_QUERY_WATCHER` |
| **ExceptionWatcher** | Exceptions with stack traces | `HASHGUARDIAN_EXCEPTION_WATCHER` |
| **JobWatcher** | Queue jobs | `HASHGUARDIAN_JOB_WATCHER` |
| **OutgoingRequestWatcher** | Outgoing HTTP calls | `HASHGUARDIAN_OUTGOING_REQUEST_WATCHER` |
| **CacheWatcher** | Cache hits, misses, writes | `HASHGUARDIAN_CACHE_WATCHER` |
| **MailWatcher** | Sent emails | `HASHGUARDIAN_MAIL_WATCHER` |
| **NotificationWatcher** | Notifications | `HASHGUARDIAN_NOTIFICATION_WATCHER` |
| **CommandWatcher** | Artisan commands | `HASHGUARDIAN_COMMAND_WATCHER` |
| **ScheduleWatcher** | Scheduled tasks | `HASHGUARDIAN_SCHEDULE_WATCHER` |
| **LogWatcher** | Log entries | `HASHGUARDIAN_LOG_WATCHER` |
| **ActivityWatcher** | User activity tracking | `HASHGUARDIAN_ACTIVITY_WATCHER` |
| **BatchWatcher** | Batch operations | `HASHGUARDIAN_BATCH_WATCHER` |
| **DumpWatcher** | `dump()` / `dd()` output | `HASHGUARDIAN_DUMP_WATCHER` |
| **EventWatcher** | Laravel events | `HASHGUARDIAN_EVENT_WATCHER` |
| **GateWatcher** | Authorization checks | `HASHGUARDIAN_GATE_WATCHER` |
| **ModelWatcher** | Eloquent model events | `HASHGUARDIAN_MODEL_WATCHER` |
| **RedisWatcher** | Redis operations | `HASHGUARDIAN_REDIS_WATCHER` |
| **ViewWatcher** | View rendering | `HASHGUARDIAN_VIEW_WATCHER` |

## Custom Metrics

Track custom business metrics in your application:

```php
use Hashcrypttech\HashGuardian\Facades\HashGuardian;

// Count events
HashGuardian::count('orders.placed');

// Record numeric metrics
HashGuardian::metric('payment.amount', 99.99);

// Time operations
HashGuardian::startTimer('api.call');
// ... your code ...
HashGuardian::stopTimer('api.call');
```

## API Access

Enable the REST API for programmatic access:

```env
HASHGUARDIAN_API_ENABLED=true
```

Create API tokens:

```bash
php artisan hashguardian:token create "My Token"
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `hashguardian:install` | Install the package (publish config, migrations, assets) |
| `hashguardian:publish` | Publish/update frontend assets |
| `hashguardian:prune` | Remove old entries |
| `hashguardian:clear` | Clear all recorded data |
| `hashguardian:pause` | Temporarily pause recording |
| `hashguardian:resume` | Resume recording |
| `hashguardian:monitor` | Start server metrics collection (long-running) |
| `hashguardian:aggregate` | Build metric aggregates for trends |
| `hashguardian:token` | Manage API tokens |
| `hashguardian:stats` | Display summary statistics |
| `hashguardian:tail` | Live-tail entries in the terminal |
| `hashguardian:report` | Generate a summary report |

## Scheduled Tasks

Add these to your `routes/console.php` or scheduler:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('hashguardian:prune')->daily();
Schedule::command('hashguardian:aggregate')->hourly();
```

## Server Monitoring

Enable server resource monitoring (CPU, RAM, disk, network):

```env
HASHGUARDIAN_SERVER_MONITORING=true
```

Run the monitor as a background process:

```bash
php artisan hashguardian:monitor
```

## Publishing Assets

To update assets after a package update:

```bash
php artisan hashguardian:publish
```

Or use vendor:publish with tags:

```bash
php artisan vendor:publish --tag=hashguardian-config
php artisan vendor:publish --tag=hashguardian-assets
php artisan vendor:publish --tag=hashguardian-provider
```

## License

HashGuardian is open-sourced software licensed under the [MIT license](LICENSE).
