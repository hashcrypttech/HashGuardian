<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class OutgoingRequestWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(ResponseReceived::class, [$this, 'handleResponseReceived']);
        $app['events']->listen(ConnectionFailed::class, [$this, 'handleConnectionFailed']);
    }

    public function handleResponseReceived(ResponseReceived $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $request = $event->request;
        $response = $event->response;

        $entry = IncomingEntry::make(EntryType::OUTGOING_REQUEST, [
            'method' => $request->method(),
            'url' => $this->sanitizeUrl($request->url()),
            'headers' => $this->redactHeaders($request->headers()),
            'body' => $this->truncateBody($request->body()),
            'status' => $response->status(),
            'response_headers' => $this->redactHeaders($response->headers()),
            'response_body' => $this->truncateBody($response->body()),
            'response_size' => strlen($response->body()),
        ]);

        $duration = $this->extractDuration($response);
        if ($duration) {
            $entry->duration($duration);
        }

        $entry->status((string) $response->status());
        $entry->familyHash(md5($request->method() . parse_url($request->url(), PHP_URL_HOST) . parse_url($request->url(), PHP_URL_PATH)));

        $tags = [
            'method:' . $request->method(),
            'host:' . parse_url($request->url(), PHP_URL_HOST),
        ];

        if ($response->status() >= 400) {
            $tags[] = 'error';
        }

        $entry->tags($tags);

        $this->record($entry);
    }

    public function handleConnectionFailed(ConnectionFailed $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $request = $event->request;

        $entry = IncomingEntry::make(EntryType::OUTGOING_REQUEST, [
            'method' => $request->method(),
            'url' => $this->sanitizeUrl($request->url()),
            'headers' => $this->redactHeaders($request->headers()),
            'body' => $this->truncateBody($request->body()),
            'status' => 'connection_failed',
            'response_headers' => [],
            'response_body' => null,
            'error' => 'Connection failed',
        ]);

        $entry->status('connection_failed');
        $entry->familyHash(md5($request->method() . parse_url($request->url(), PHP_URL_HOST) . parse_url($request->url(), PHP_URL_PATH)));
        $entry->tags([
            'method:' . $request->method(),
            'host:' . parse_url($request->url(), PHP_URL_HOST),
            'error',
            'connection_failed',
        ]);

        $this->record($entry);
    }

    protected function sanitizeUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);

            $sensitiveKeys = ['key', 'api_key', 'apikey', 'secret', 'token', 'access_token', 'password'];
            foreach ($sensitiveKeys as $key) {
                if (isset($params[$key])) {
                    $params[$key] = '********';
                }
            }

            $parsed['query'] = http_build_query($params);
        }

        return $this->buildUrl($parsed);
    }

    protected function buildUrl(array $parts): string
    {
        $url = '';
        if (isset($parts['scheme'])) $url .= $parts['scheme'] . '://';
        if (isset($parts['host'])) $url .= $parts['host'];
        if (isset($parts['port'])) $url .= ':' . $parts['port'];
        if (isset($parts['path'])) $url .= $parts['path'];
        if (isset($parts['query'])) $url .= '?' . $parts['query'];
        if (isset($parts['fragment'])) $url .= '#' . $parts['fragment'];

        return $url;
    }

    protected function redactHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'x-api-key', 'x-secret', 'cookie', 'set-cookie'];

        foreach ($sensitive as $key) {
            if (isset($headers[$key])) {
                $headers[$key] = '********';
            }
            $upperKey = str_replace('-', '_', strtoupper($key));
            if (isset($headers[$upperKey])) {
                $headers[$upperKey] = '********';
            }
        }

        return $headers;
    }

    protected function truncateBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $sizeLimit = $this->option('size_limit', 64) * 1024;

        if (strlen($body) > $sizeLimit) {
            return substr($body, 0, $sizeLimit) . '... (truncated)';
        }

        return $body;
    }

    protected function extractDuration(Response $response): ?float
    {
        $transferTime = $response->transferStats?->getTransferTime();

        if ($transferTime !== null) {
            return $transferTime * 1000;
        }

        return null;
    }
}
