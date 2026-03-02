<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Foundation\Application;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class CommandWatcher extends Watcher
{
    protected array $commandStartTimes = [];

    public function register(Application $app): void
    {
        $app['events']->listen(CommandStarting::class, [$this, 'handleCommandStarting']);
        $app['events']->listen(CommandFinished::class, [$this, 'handleCommandFinished']);
    }

    public function handleCommandStarting(CommandStarting $event): void
    {
        if ($event->command) {
            $this->commandStartTimes[$event->command] = microtime(true);
        }
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $command = $event->command ?? 'unknown';

        if ($this->shouldIgnore($command)) {
            return;
        }

        $duration = isset($this->commandStartTimes[$command])
            ? (microtime(true) - $this->commandStartTimes[$command]) * 1000
            : null;

        unset($this->commandStartTimes[$command]);

        $entry = IncomingEntry::make(EntryType::COMMAND, [
            'command' => $command,
            'exit_code' => $event->exitCode,
            'arguments' => $this->extractArguments($event),
            'options' => $this->extractOptions($event),
        ]);

        $entry->duration($duration);
        $entry->status($event->exitCode === 0 ? 'success' : 'error');
        $entry->familyHash(md5($command));
        $entry->tags([
            'command:' . $command,
            'exit:' . $event->exitCode,
        ]);

        $this->record($entry);
    }

    protected function shouldIgnore(string $command): bool
    {
        if (str_starts_with($command, 'hashguardian:')) {
            return true;
        }

        $ignoreCommands = config('hashguardian.ignore_commands', []);

        foreach ($ignoreCommands as $pattern) {
            if ($command === $pattern || fnmatch($pattern, $command)) {
                return true;
            }
        }

        return false;
    }

    protected function extractArguments(CommandFinished $event): ?string
    {
        if ($event->input) {
            return (string) $event->input;
        }

        return null;
    }

    protected function extractOptions(CommandFinished $event): ?array
    {
        if (! $event->input) {
            return null;
        }

        try {
            if (method_exists($event->input, 'getOptions')) {
                $options = $event->input->getOptions();
                return array_filter($options, fn($v) => $v !== false && $v !== null);
            }
        } catch (\Throwable $e) {
            // Input may not be bound
        }

        return null;
    }
}
