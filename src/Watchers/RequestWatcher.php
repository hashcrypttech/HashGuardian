<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;
use Hashcrypttech\HashGuardian\ServerMetricsCollector;

class RequestWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(RequestHandled::class, [$this, 'handleRequestHandled']);
    }

    public function handleRequestHandled(RequestHandled $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldIgnore($event->request)) {
            return;
        }

        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
        $duration = $startTime ? (microtime(true) - $startTime) * 1000 : null;

        $statusCode = $event->response->getStatusCode();

        $entry = IncomingEntry::make(EntryType::REQUEST, [
            'method' => $event->request->method(),
            'uri' => $event->request->path(),
            'url' => $event->request->fullUrl(),
            'status' => $statusCode,
            'ip' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
            'headers' => $this->redactHeaders($this->extractHeaders($event->request)),
            'payload' => $this->extractPayload($event->request),
            'response_status' => $statusCode,
            'response_size' => $this->getResponseSize($event->response),
            'user' => $this->extractUser($event->request),
            'middleware' => $this->getMiddleware($event->request),
            'controller_action' => $this->getControllerAction($event->request),
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);

        $entry->duration($duration);
        $entry->status((string) $statusCode);
        $entry->familyHash(md5($event->request->method() . $event->request->path()));

        $tags = ['method:' . $event->request->method()];

        if ($routeName = $event->request->route()?->getName()) {
            $tags[] = 'route:' . $routeName;
        }

        if ($userId = $event->request->user()?->getAuthIdentifier()) {
            $tags[] = 'user:' . $userId;
        }

        $entry->tags($tags);

        $this->record($entry);
    }

    protected function shouldIgnore(Request $request): bool
    {
        $ignorePaths = config('hashguardian.ignore_paths', []);

        foreach ($ignorePaths as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return true;
            }
        }

        $ignoreStatusCodes = $this->option('ignore_status_codes', []);

        return false;
    }

    protected function extractHeaders(Request $request): array
    {
        $headers = collect($request->headers->all())
            ->map(fn($values) => implode(', ', $values))
            ->toArray();

        return $headers;
    }

    protected function redactHeaders(array $headers): array
    {
        $redacted = ['authorization', 'cookie', 'set-cookie', 'proxy-authorization', 'x-xsrf-token'];

        foreach ($redacted as $key) {
            if (isset($headers[$key])) {
                $headers[$key] = '********';
            }
        }

        return $headers;
    }

    protected function extractPayload(Request $request): ?array
    {
        $sizeLimit = $this->option('size_limit', 64) * 1024;

        $payload = $request->except([
            '_token', 'password', 'password_confirmation',
            'current_password', 'new_password',
        ]);

        $encoded = json_encode($payload);
        if ($encoded && strlen($encoded) > $sizeLimit) {
            return ['_truncated' => 'Payload exceeds size limit (' . $this->option('size_limit', 64) . 'KB)'];
        }

        return $payload ?: null;
    }

    protected function extractUser(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->getAuthIdentifier(),
            'name' => $user->name ?? null,
            'email' => $user->email ?? null,
        ];
    }

    protected function getResponseSize(Response $response): ?int
    {
        $content = $response->getContent();

        return $content !== false ? strlen($content) : null;
    }

    protected function getMiddleware(Request $request): array
    {
        $route = $request->route();

        if (! $route) {
            return [];
        }

        return method_exists($route, 'gatherMiddleware')
            ? $route->gatherMiddleware()
            : [];
    }

    protected function getControllerAction(Request $request): ?string
    {
        $route = $request->route();

        if (! $route) {
            return null;
        }

        $action = $route->getActionName();

        return $action !== 'Closure' ? $action : null;
    }

    protected function getResourceDelta(Request $request): array
    {
        $startSnapshot = $request->attributes->get('hashguardian_resource_snapshot');

        if (! $startSnapshot) {
            return [];
        }

        $endSnapshot = ServerMetricsCollector::snapshot();
        $delta = ServerMetricsCollector::delta($startSnapshot, $endSnapshot);

        return [
            'resource_usage' => [
                'memory_start_mb' => round($delta['memory_start'] / 1024 / 1024, 2),
                'memory_peak_mb' => round($delta['memory_peak'] / 1024 / 1024, 2),
                'memory_delta_mb' => round($delta['memory_delta'] / 1024 / 1024, 2),
                'cpu_user_ms' => round($delta['cpu_user_time_us'] / 1000, 2),
                'cpu_system_ms' => round($delta['cpu_system_time_us'] / 1000, 2),
                'cpu_total_ms' => round($delta['cpu_total_time_us'] / 1000, 2),
            ],
        ];
    }
}
