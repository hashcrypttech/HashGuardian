<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class GateWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(GateEvaluated::class, [$this, 'handleGateEvaluated']);
    }

    public function handleGateEvaluated(GateEvaluated $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldIgnore($event->ability)) {
            return;
        }

        $caller = $this->getCallerInfo();
        $result = $this->resolveResult($event->result);

        $entry = IncomingEntry::make(EntryType::GATE, [
            'ability' => $event->ability,
            'result' => $result,
            'message' => $this->resolveMessage($event->result),
            'arguments' => $this->formatArguments($event->arguments),
            'file' => $caller['file'] ?? null,
            'line' => $caller['line'] ?? null,
        ]);

        $entry->familyHash(md5($event->ability));
        $entry->status($result);
        $entry->tags([
            'ability:' . $event->ability,
            'result:' . $result,
        ]);

        $this->record($entry);
    }

    protected function shouldIgnore(string $ability): bool
    {
        return Str::is($this->option('ignore_abilities', []), $ability);
    }

    protected function resolveResult(mixed $result): string
    {
        if ($result instanceof Response) {
            return $result->allowed() ? 'allowed' : 'denied';
        }

        return $result ? 'allowed' : 'denied';
    }

    protected function resolveMessage(mixed $result): ?string
    {
        if ($result instanceof Response) {
            return $result->message();
        }

        return null;
    }

    protected function formatArguments(array $arguments): array
    {
        return collect($arguments)->map(function ($argument) {
            if ($argument instanceof Model) {
                return get_class($argument) . ':' . $argument->getKey();
            }

            if (is_object($argument)) {
                return get_class($argument);
            }

            return $argument;
        })->toArray();
    }

    protected function getCallerInfo(): array
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

            if (str_contains($file, '/app/') || str_contains($file, '/routes/')) {
                return [
                    'file' => $file,
                    'line' => $frame['line'] ?? null,
                ];
            }
        }

        return [];
    }
}
