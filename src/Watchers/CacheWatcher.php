<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class CacheWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(CacheHit::class, [$this, 'handleCacheHit']);
        $app['events']->listen(CacheMissed::class, [$this, 'handleCacheMissed']);
        $app['events']->listen(KeyWritten::class, [$this, 'handleKeyWritten']);
        $app['events']->listen(KeyForgotten::class, [$this, 'handleKeyForgotten']);
    }

    public function handleCacheHit(CacheHit $event): void
    {
        if (! $this->shouldRecord($event->key)) {
            return;
        }

        $this->recordCacheEvent('hit', $event->key, $event->value, [
            'store' => property_exists($event, 'storeName') ? $event->storeName : null,
        ]);
    }

    public function handleCacheMissed(CacheMissed $event): void
    {
        if (! $this->shouldRecord($event->key)) {
            return;
        }

        $this->recordCacheEvent('missed', $event->key, null, [
            'store' => property_exists($event, 'storeName') ? $event->storeName : null,
        ]);
    }

    public function handleKeyWritten(KeyWritten $event): void
    {
        if (! $this->shouldRecord($event->key)) {
            return;
        }

        $this->recordCacheEvent('write', $event->key, $event->value, [
            'store' => property_exists($event, 'storeName') ? $event->storeName : null,
            'seconds' => $event->seconds ?? null,
        ]);
    }

    public function handleKeyForgotten(KeyForgotten $event): void
    {
        if (! $this->shouldRecord($event->key)) {
            return;
        }

        $this->recordCacheEvent('forget', $event->key, null, [
            'store' => property_exists($event, 'storeName') ? $event->storeName : null,
        ]);
    }

    protected function recordCacheEvent(string $type, string $key, mixed $value, array $extra = []): void
    {
        $entry = IncomingEntry::make(EntryType::CACHE, array_merge([
            'type' => $type,
            'key' => $key,
            'value_type' => $this->getValueType($value),
            'value_size' => $this->getValueSize($value),
        ], $extra));

        $entry->familyHash(md5($type . $this->normalizeKey($key)));
        $entry->status($type);
        $entry->tags([
            'type:' . $type,
            'key_prefix:' . $this->getKeyPrefix($key),
        ]);

        $this->record($entry);
    }

    protected function shouldRecord(string $key): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $ignoreKeys = $this->option('ignore_keys', []);

        foreach ($ignoreKeys as $pattern) {
            if (Str::is($pattern, $key)) {
                return false;
            }
        }

        return true;
    }

    protected function getValueType(mixed $value): string
    {
        if (is_null($value)) return 'null';
        if (is_array($value)) return 'array';
        if (is_object($value)) return get_class($value);
        if (is_string($value)) return 'string';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'float';
        if (is_bool($value)) return 'boolean';

        return gettype($value);
    }

    protected function getValueSize(mixed $value): ?int
    {
        if (is_null($value)) return null;
        if (is_string($value)) return strlen($value);

        $serialized = serialize($value);

        return strlen($serialized);
    }

    protected function normalizeKey(string $key): string
    {
        return preg_replace('/\d+/', '*', $key);
    }

    protected function getKeyPrefix(string $key): string
    {
        $parts = explode(':', $key);

        return $parts[0] ?? $key;
    }
}
