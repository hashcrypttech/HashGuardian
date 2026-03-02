<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class ExceptionWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(MessageLogged::class, [$this, 'handleMessageLogged']);
    }

    public function handleMessageLogged(MessageLogged $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($event->level !== 'error' && $event->level !== 'critical' && $event->level !== 'emergency') {
            return;
        }

        $exception = $event->context['exception'] ?? null;

        if (! $exception instanceof \Throwable) {
            return;
        }

        $trace = $exception->getTrace();
        $formattedTrace = $this->formatStackTrace($trace);

        $entry = IncomingEntry::make(EntryType::EXCEPTION, [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $formattedTrace,
            'source_code' => $this->option('capture_source_code', true)
                ? $this->getSourceCode($exception->getFile(), $exception->getLine())
                : null,
            'severity' => $event->level,
            'previous' => $exception->getPrevious() ? [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
            ] : null,
        ]);

        $entry->familyHash($this->familyHash($exception));
        $entry->status($event->level);
        $entry->tags([
            'exception:' . get_class($exception),
            'file:' . $exception->getFile(),
        ]);

        $this->record($entry);
    }

    protected function familyHash(\Throwable $exception): string
    {
        return md5(
            get_class($exception) .
            $exception->getFile() .
            $exception->getLine() .
            $exception->getCode()
        );
    }

    protected function formatStackTrace(array $trace): array
    {
        return collect($trace)->take(30)->map(function ($frame) {
            return Arr::only($frame, ['file', 'line', 'function', 'class', 'type']);
        })->toArray();
    }

    protected function getSourceCode(string $file, int $line): ?array
    {
        if (! file_exists($file)) {
            return null;
        }

        $linesAround = (int) $this->option('source_code_lines', 10);

        try {
            $fileLines = file($file);
            $start = max(0, $line - $linesAround - 1);
            $end = min(count($fileLines), $line + $linesAround);

            $source = [];
            for ($i = $start; $i < $end; $i++) {
                $source[] = [
                    'line' => $i + 1,
                    'code' => rtrim($fileLines[$i] ?? ''),
                    'highlight' => ($i + 1) === $line,
                ];
            }

            return $source;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
