<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Redis\Events\CommandExecuted;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class RedisWatcher extends Watcher
{
    public function register(Application $app): void
    {
        if (! $app->bound('redis')) {
            return;
        }

        $app['events']->listen(CommandExecuted::class, [$this, 'handleCommandExecuted']);

        foreach ((array) $app['redis']->connections() as $connection) {
            $connection->setEventDispatcher($app['events']);
        }

        $app['redis']->enableEvents();
    }

    public function handleCommandExecuted(CommandExecuted $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldIgnoreCommand($event->command)) {
            return;
        }

        $formattedCommand = $this->formatCommand($event->command, $event->parameters);

        $entry = IncomingEntry::make(EntryType::REDIS, [
            'connection' => $event->connectionName,
            'command' => $formattedCommand,
            'time' => number_format($event->time, 2, '.', ''),
        ]);

        $entry->duration($event->time);
        $entry->familyHash(md5($event->command));
        $entry->tags([
            'connection:' . $event->connectionName,
            'command:' . strtoupper($event->command),
        ]);

        $this->record($entry);
    }

    protected function formatCommand(string $command, array $parameters): string
    {
        $formatted = collect($parameters)->map(function ($parameter) {
            if (is_array($parameter)) {
                return collect($parameter)->map(function ($value, $key) {
                    if (is_array($value)) {
                        return json_encode($value);
                    }

                    return is_int($key) ? $value : "{$key} {$value}";
                })->implode(' ');
            }

            return $parameter;
        })->implode(' ');

        return "{$command} {$formatted}";
    }

    protected function shouldIgnoreCommand(string $command): bool
    {
        return in_array(strtolower($command), [
            'pipeline', 'transaction',
        ]);
    }
}
