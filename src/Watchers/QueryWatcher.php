<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class QueryWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(QueryExecuted::class, [$this, 'handleQueryExecuted']);
    }

    public function handleQueryExecuted(QueryExecuted $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldIgnoreQuery($event)) {
            return;
        }

        $sql = $event->sql;
        $duration = $event->time;
        $slowThreshold = $this->option('slow', 100);

        $entry = IncomingEntry::make(EntryType::QUERY, [
            'sql' => $sql,
            'bindings' => $this->formatBindings($event),
            'connection' => $event->connectionName,
            'time' => $duration,
            'slow' => $duration >= $slowThreshold,
            'file' => $this->getCallerFile(),
            'line' => $this->getCallerLine(),
            'hash' => md5($sql),
        ]);

        $entry->duration($duration);
        $entry->familyHash(md5($this->normalizeQuery($sql)));
        $entry->status($duration >= $slowThreshold ? 'slow' : 'ok');

        $tags = ['connection:' . $event->connectionName];

        if ($duration >= $slowThreshold) {
            $tags[] = 'slow';
        }

        $queryType = $this->detectQueryType($sql);
        if ($queryType) {
            $tags[] = 'type:' . $queryType;
        }

        $entry->tags($tags);

        $this->record($entry);
    }

    protected function shouldIgnoreQuery(QueryExecuted $event): bool
    {
        if ($this->option('ignore_packages', true)) {
            $sql = strtolower($event->sql);

            if (str_contains($sql, 'hashguardian_')) {
                return true;
            }

            $telescopeTables = ['telescope_entries', 'telescope_entries_tags', 'telescope_monitoring'];
            foreach ($telescopeTables as $table) {
                if (str_contains($sql, $table)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function formatBindings(QueryExecuted $event): array
    {
        return collect($event->bindings)->map(function ($binding) {
            if ($binding instanceof \DateTimeInterface) {
                return $binding->format('Y-m-d H:i:s');
            }

            if (is_string($binding) && strlen($binding) > 255) {
                return substr($binding, 0, 255) . '... (truncated)';
            }

            return $binding;
        })->toArray();
    }

    protected function normalizeQuery(string $sql): string
    {
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);
        $normalized = preg_replace("/'.+?'/", '?', $normalized);
        $normalized = preg_replace('/".+?"/', '?', $normalized);

        return trim($normalized);
    }

    protected function detectQueryType(string $sql): ?string
    {
        $sql = ltrim($sql);

        if (stripos($sql, 'select') === 0) return 'select';
        if (stripos($sql, 'insert') === 0) return 'insert';
        if (stripos($sql, 'update') === 0) return 'update';
        if (stripos($sql, 'delete') === 0) return 'delete';
        if (stripos($sql, 'alter') === 0) return 'alter';
        if (stripos($sql, 'create') === 0) return 'create';

        return null;
    }

    protected function getCallerFile(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';

            if (str_contains($file, '/vendor/')) {
                continue;
            }

            if (str_contains($file, '/packages/webz/hashguardian/')) {
                continue;
            }

            if (str_contains($file, '/app/') || str_contains($file, '/database/')) {
                return $file;
            }
        }

        return null;
    }

    protected function getCallerLine(): ?int
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';

            if (str_contains($file, '/vendor/') || str_contains($file, '/packages/webz/hashguardian/')) {
                continue;
            }

            if (str_contains($file, '/app/') || str_contains($file, '/database/')) {
                return $frame['line'] ?? null;
            }
        }

        return null;
    }
}
