<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Foundation\Application;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class ScheduleWatcher extends Watcher
{
    protected array $taskStartTimes = [];

    public function register(Application $app): void
    {
        $app['events']->listen(ScheduledTaskStarting::class, [$this, 'handleTaskStarting']);
        $app['events']->listen(ScheduledTaskFinished::class, [$this, 'handleTaskFinished']);
    }

    public function handleTaskStarting(ScheduledTaskStarting $event): void
    {
        $taskId = $this->getTaskIdentifier($event->task);
        $this->taskStartTimes[$taskId] = microtime(true);
    }

    public function handleTaskFinished(ScheduledTaskFinished $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $task = $event->task;
        $taskId = $this->getTaskIdentifier($task);

        $duration = isset($this->taskStartTimes[$taskId])
            ? (microtime(true) - $this->taskStartTimes[$taskId]) * 1000
            : null;

        unset($this->taskStartTimes[$taskId]);

        $command = $task->command ?? $task->description ?? 'Unknown';

        $entry = IncomingEntry::make(EntryType::SCHEDULE, [
            'command' => $this->cleanCommand($command),
            'description' => $task->description,
            'expression' => $task->expression,
            'timezone' => $task->timezone,
            'without_overlapping' => $task->withoutOverlapping ?? false,
            'on_one_server' => $task->onOneServer ?? false,
            'exit_code' => $task->exitCode ?? null,
            'output' => $this->getTaskOutput($task),
        ]);

        $entry->duration($duration);
        $entry->familyHash(md5($this->cleanCommand($command) . $task->expression));

        $isSuccess = ($task->exitCode ?? 0) === 0;
        $entry->status($isSuccess ? 'success' : 'error');

        $entry->tags([
            'command:' . $this->cleanCommand($command),
            'status:' . ($isSuccess ? 'success' : 'error'),
        ]);

        $this->record($entry);
    }

    protected function getTaskIdentifier($task): string
    {
        return md5(($task->command ?? '') . ($task->expression ?? '') . ($task->description ?? ''));
    }

    protected function cleanCommand(string $command): string
    {
        $command = str_replace([
            "'",
            '"',
            PHP_BINARY,
            'artisan',
        ], '', $command);

        return trim(preg_replace('/\s+/', ' ', $command));
    }

    protected function getTaskOutput($task): ?string
    {
        if (! property_exists($task, 'output') || ! $task->output) {
            return null;
        }

        if ($task->output === '/dev/null' || $task->output === 'NUL') {
            return null;
        }

        if (file_exists($task->output)) {
            $output = file_get_contents($task->output);
            return mb_substr($output, 0, 2000);
        }

        return null;
    }
}
