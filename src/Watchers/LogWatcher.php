<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\Events\MessageLogged;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class LogWatcher extends Watcher
{
    protected static array $logLevels = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7,
    ];

    public function register(Application $app): void
    {
        $app['events']->listen(MessageLogged::class, [$this, 'handleMessageLogged']);
    }

    public function handleMessageLogged(MessageLogged $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->isExceptionLog($event)) {
            return;
        }

        if (! $this->meetsLevelThreshold($event->level)) {
            return;
        }

        $entry = IncomingEntry::make(EntryType::LOG, [
            'level' => $event->level,
            'message' => $this->formatMessage($event->message),
            'context' => $this->sanitizeContext($event->context),
        ]);

        $entry->status($event->level);
        $entry->familyHash(md5($event->level . $this->normalizeMessage($event->message)));
        $entry->tags([
            'level:' . $event->level,
        ]);

        $this->record($entry);
    }

    protected function isExceptionLog(MessageLogged $event): bool
    {
        return isset($event->context['exception']) && $event->context['exception'] instanceof \Throwable;
    }

    protected function meetsLevelThreshold(string $level): bool
    {
        $configLevel = $this->option('level', 'error');

        $currentPriority = self::$logLevels[$level] ?? 0;
        $thresholdPriority = self::$logLevels[$configLevel] ?? 0;

        return $currentPriority >= $thresholdPriority;
    }

    protected function formatMessage($message): string
    {
        if (is_string($message)) {
            return mb_substr($message, 0, 5000);
        }

        return (string) $message;
    }

    protected function normalizeMessage($message): string
    {
        $message = (string) $message;

        $normalized = preg_replace('/\b\d+\b/', '#', $message);
        $normalized = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '{uuid}', $normalized);

        return $normalized;
    }

    protected function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'api_key', 'authorization'];

        $sanitized = [];
        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '********';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } elseif (is_object($value)) {
                if ($value instanceof \Throwable) {
                    continue;
                }
                $sanitized[$key] = get_class($value);
            } elseif (is_string($value) && strlen($value) > 1000) {
                $sanitized[$key] = substr($value, 0, 1000) . '... (truncated)';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
