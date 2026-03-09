# HashGuardian — Features List

> Self-hosted monitoring and observability dashboard for Laravel applications.

---

## Overview

HashGuardian is a comprehensive, self-hosted alternative to Laravel Telescope that provides deep observability into your Laravel application. It monitors requests, queries, exceptions, jobs, server resources, and more — all within your own infrastructure.

- **PHP**: 8.2+
- **Laravel**: 11.x / 12.x
- **License**: MIT

---

## Why HashGuardian?

### The Problem

Laravel applications in production are black boxes without proper observability. When something goes wrong — a slow page load, a failed job, a spike in errors — teams often find themselves digging through log files, guessing at root causes, and struggling to reproduce issues. Third-party monitoring services solve this but come with recurring costs, data privacy concerns, and dependency on external infrastructure.

### How HashGuardian Helps

**For Developers:**
- **Debug faster** — Trace any request end-to-end: see the exact queries fired, cache hits/misses, events dispatched, and views rendered in a single timeline view. No more piecing together log entries manually.
- **Catch slow queries early** — The slow query detector flags database queries exceeding your threshold before they become production bottlenecks.
- **Spot N+1 problems** — See every query a request triggers, making it trivial to identify N+1 query issues and optimize eager loading.
- **Track down exceptions** — Full stack traces with source code snippets and previous exception chains mean you understand *why* something failed, not just *that* it failed.
- **Monitor external dependencies** — Outgoing HTTP request tracking reveals when third-party APIs slow down or fail, so you know the problem isn't in your code.

**For DevOps & System Administrators:**
- **Server health at a glance** — CPU, RAM, disk, network, and process monitoring in a single dashboard. The htop-like view gives you real-time process visibility without SSH access.
- **Correlate application and infrastructure** — See how server resource usage relates to application performance. A CPU spike during a batch job? HashGuardian connects the dots.
- **Proactive alerting** — Digest reports delivered daily or weekly keep your team informed of trends before they become incidents.
- **Capacity planning** — Historical trend data with hourly/daily/weekly/monthly rollups helps you anticipate when resources will need scaling.

**For Teams & Organizations:**
- **Complete data ownership** — All monitoring data stays on your infrastructure. No sensitive request payloads, user data, or business metrics leave your servers.
- **Zero recurring cost** — No per-seat pricing, no usage tiers, no surprise bills. Install once and monitor indefinitely.
- **Onboard new developers faster** — New team members can explore how the application behaves in real time, understanding request flows, job processing, and event handling without reading every line of code.
- **Audit user activity** — Track which users performed what actions and when, useful for compliance, debugging user-reported issues, and security review.
- **Share insights via API** — The REST API lets you integrate monitoring data into Slack bots, custom dashboards, CI/CD pipelines, or any external tool your team uses.

### Use Cases

| Scenario | How HashGuardian Helps |
|----------|----------------------|
| **Production debugging** | Timeline view groups all entries by request, showing queries, cache, events, and views in order — pinpoint the exact failure point. |
| **Performance optimization** | Slow query detection, P95 response times, and trend analysis reveal where optimization effort will have the most impact. |
| **Post-deployment validation** | Compare error rates, response times, and server metrics before and after a deploy using trend comparison. |
| **Queue monitoring** | Track job success/failure rates, processing duration, and queue backlogs to keep background processing healthy. |
| **Security auditing** | Activity watcher logs every authenticated user action with timestamps, IPs, and session data. |
| **Client reporting** | Export monitoring data as CSV/JSON or generate digest reports to share application health with stakeholders. |
| **Cost reduction** | Replace paid monitoring services (Telescope Pro, Sentry, Datadog, New Relic) for Laravel-specific observability without sacrificing depth. |
| **Multi-environment monitoring** | Run HashGuardian on staging and production with separate databases to compare application behavior across environments. |

### Comparison with Alternatives

| Feature | HashGuardian | Laravel Telescope | Paid SaaS (Sentry, Datadog, etc.) |
|---------|-------------|-------------------|-----------------------------------|
| Self-hosted | Yes | Yes | No |
| 20 built-in watchers | Yes | ~16 watchers | Varies |
| Server resource monitoring | Yes | No | Yes (agent required) |
| Custom business metrics | Yes | No | Yes |
| REST API access | Yes | No | Yes |
| Trend analysis & aggregation | Yes | No | Yes |
| Data export (CSV/JSON) | Yes | No | Yes |
| Digest email reports | Yes | No | Yes |
| htop-like process monitor | Yes | No | No |
| User activity tracking | Yes | No | Varies |
| Dark mode | Yes | Yes | Varies |
| Cost | Free (MIT) | Free (MIT) | $29–$500+/month |

---

## 1. Application Monitoring (20 Watchers)

HashGuardian ships with 20 built-in watchers, each individually configurable via environment variables or the config file.

### Request & Response Tracking
- Full HTTP request/response logging with status codes, headers, and payloads
- Response size, memory usage, and duration tracking
- IP address, user agent, and controller action capture
- Automatic sensitive header redaction

### Database Query Monitoring
- SQL query capture with bindings and execution duration
- **Slow query detection** with configurable threshold
- Connection name and query type identification
- Caller file/line tracing for query origin

### Exception Tracking
- Full stack traces (up to 30 frames)
- **Source code snippet capture** with surrounding line context
- Severity level classification
- Previous exception chain tracking

### Queue Job Monitoring
- Job lifecycle tracking: queued → processing → processed/failed
- Job name, queue, connection, payload, and duration
- Job ID tracking and batch association

### Outgoing HTTP Request Tracking
- External API call monitoring with method, URL, headers
- Request/response body capture (with size truncation)
- Status code, response size, and duration measurement

### Cache Monitoring
- Hit, miss, write, and forget event tracking
- Key names, store names, and TTL values
- Configurable key pattern ignoring

### Mail Tracking
- Mailable class, subject, and recipient tracking
- To, CC, BCC, from, and reply-to address capture
- Queued mail status detection

### Notification Tracking
- Notification class and channel identification
- Notifiable type and response capture
- Queued notification detection

### Artisan Command Tracking
- Command name, arguments, options, and exit code
- Duration and success/error status

### Scheduled Task Monitoring
- Task name, command executed, and duration
- Success/failure status tracking

### Log Monitoring
- All log levels from debug to emergency
- Message and context data capture
- Configurable minimum log level threshold

### User Activity Tracking
- Authenticated user identification
- Action/method, URI, route name, and session tracking
- IP, user agent, and referer logging
- Configurable path and method ignoring

### Batch Operation Monitoring
- Batch ID, name, and queue/connection tracking
- Job counts: total, pending, and failed
- Allows-failures flag tracking

### Dump Watcher
- Captures `dump()` and `dd()` output with HTML formatting
- Caller file and line identification
- Toggleable via cache flag

### Event Monitoring
- Laravel event name and payload extraction
- Listener count and broadcast status
- Framework event filtering

### Gate / Authorization Monitoring
- Ability name, allow/deny result, and arguments
- Caller information capture

### Eloquent Model Monitoring
- Model class, event type (created/updated/deleted/retrieved)
- Change tracking and model key identification
- Hydration event tracking

### Redis Monitoring
- Command name, connection, and parameters
- Operation duration tracking

### View Monitoring
- View name, path, and data keys
- Composer tracking and configurable view ignoring

---

## 2. Server Resource Monitoring

Real-time server health tracking with a dedicated dashboard.

- **CPU**: Usage percentage, load averages (1m/5m/15m), core count
- **RAM**: Used, available, and percentage utilization
- **Swap**: Used, available, and percentage utilization
- **Disk**: Used, available, and percentage per mount point
- **Network**: Bytes and packets in/out with delta calculation
- **Process Monitoring**: Count, list, and per-process memory usage
- **PHP Metrics**: Memory limit, usage, open files, and thread count
- **htop-like Process Monitor**: Real-time terminal-style process view in the browser
- Configurable collection interval (default: 15 seconds)
- Long-running background collector with graceful shutdown

---

## 3. Web Dashboard

A full-featured monitoring dashboard accessible at `/hashguardian`.

- **25+ dedicated pages** for each monitoring section
- Real-time charts and interactive data tables
- Filtering, sorting, and pagination across all sections
- **Timeline view** to trace a full request lifecycle (grouped by batch ID)
- **Trend comparison** with historical data overlays
- **Performance correlation analysis** between server metrics and application events
- **Dark mode** support
- Configurable URL prefix and domain restriction

---

## 4. Trend Analysis & Aggregation

Historical metrics analysis for identifying patterns and regressions.

- **Hourly aggregation** of all entry types and server metrics
- **Rollup strategy**: hourly → daily → weekly → monthly
- Aggregated statistics include:
  - Request count, average/P95 duration, error rate, throughput
  - Query count and slow query detection
  - Job success/failure counts
  - Exception frequency
  - Cache hit ratio
  - Server CPU and memory trends
- Configurable retention: raw entries (30 days), aggregates (365 days)

---

## 5. Custom Business Metrics

Track application-specific KPIs using a simple API.

```php
use Hashcrypttech\HashGuardian\Facades\HashGuardian;

// Counters — track event occurrences
HashGuardian::count('orders.placed');
HashGuardian::count('users.signup', 1, ['plan' => 'pro']);

// Gauges — record numeric values
HashGuardian::metric('payment.amount', 99.99);

// Timers — measure operation duration
HashGuardian::startTimer('api.call');
// ... your code ...
HashGuardian::stopTimer('api.call');
```

- Counter, gauge, and timer metric types
- Metadata tags for filtering and grouping
- Dedicated metrics dashboard page

---

## 6. REST API

Programmatic access to all monitoring data with token-based authentication.

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/stats` | Dashboard summary statistics |
| GET | `/api/v1/trends` | Trend and aggregation data |
| GET | `/api/v1/activity` | User activity data |
| GET | `/api/v1/entries/{type}` | Entries filtered by type |
| GET | `/api/v1/entries/{type}/{uuid}` | Single entry detail |
| GET | `/api/v1/metrics` | Custom metrics data |
| POST | `/api/v1/export` | Initiate data export |
| GET | `/api/v1/export/{id}` | Check export job status |

### Authentication & Security
- Bearer token authentication
- SHA-256 token hashing in the database
- Ability-based access control per token
- Token expiration support
- Rate limiting (configurable per minute)
- Last-used timestamp tracking

---

## 7. Data Export

Export monitoring data for external analysis or archival.

- **CSV export** with configurable column selection
- **JSON export** with timestamps
- Background processing via queued jobs for large datasets
- Configurable max rows per export (default: 10,000)
- Configurable storage disk

---

## 8. Digest Reports

Automated email summaries of application health.

- **Daily or weekly** scheduling
- Configurable delivery time and recipient list
- Report sections include:
  - Request statistics (total, average duration, max duration)
  - Exception summary (top 5 recent)
  - Job statistics
  - Server health overview
  - User activity summary
- Configurable section inclusion/exclusion

---

## 9. Artisan Commands

12 CLI commands for management and operations.

| Command | Description |
|---------|-------------|
| `hashguardian:install` | Publish config, migrations, assets, and run migrations |
| `hashguardian:publish` | Update frontend assets after package updates |
| `hashguardian:prune` | Remove entries older than the retention period |
| `hashguardian:clear` | Delete all recorded data |
| `hashguardian:pause` | Temporarily pause all recording |
| `hashguardian:resume` | Resume recording after a pause |
| `hashguardian:monitor` | Start the server metrics collector (long-running) |
| `hashguardian:aggregate` | Build hourly aggregates and run rollups |
| `hashguardian:token` | Create, list, revoke, and refresh API tokens |
| `hashguardian:stats` | Display summary statistics in the terminal |
| `hashguardian:tail` | Live-tail entries in real time from the terminal |
| `hashguardian:report` | Generate and send a digest report |

---

## 10. Recording Controls

Fine-grained control over what gets recorded and when.

- **Global toggle**: Enable/disable all recording via config or environment variable
- **Pause/Resume**: Temporarily stop recording without config changes
- **Probabilistic sampling**: Set a sampling rate (0.0–1.0) per entry type
- **Filter callbacks**: Programmatically skip entries based on custom logic
- **Path ignoring**: Exclude specific URL paths from recording
- **Command ignoring**: Exclude specific Artisan commands from recording
- **Per-watcher configuration**: Enable/disable each of the 20 watchers independently

---

## 11. Authorization & Security

- **Dashboard access**: Restricted to `local` environment by default
- **Custom auth gate**: Define who can access the dashboard in production
- **Sensitive data redaction**: Automatic header and payload sanitization
- **Request payload size limiting**: Configurable KB limit on stored payloads
- **Separate database connection**: Store monitoring data in a dedicated database
- **API token security**: Hashed storage, expiration, and ability restrictions

---

## 12. Architecture & Performance

- **Repository pattern** for storage abstraction
- **Chunked batch insertion** to minimize database overhead
- **Batch ID grouping** to correlate related entries within a single request
- **Family hash** for grouping similar entry types
- **Tag-based filtering** via a dedicated tags table
- **PostgreSQL compatibility** alongside MySQL
- **Graceful termination hooks** for data persistence
- **Configurable data retention** with automatic pruning

---

## 13. Integration & Extensibility

- **Auto-discovery**: No manual service provider or facade registration
- **Publishable provider stub**: Customize authorization and filtering logic
- **Facade API**: Full programmatic access to recording, filtering, and metrics
- **Post-storage hooks**: Execute callbacks after entries are persisted
- **Middleware stack**: Authorize, TrackActivity, ResourceSnapshot, AuthenticateApi

---

*Developed and maintained by [Hashcrypt Technologies Pvt. Ltd.](https://hashcrypt.com/)*
