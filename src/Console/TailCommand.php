<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Hashcrypttech\HashGuardian\EntryType;

class TailCommand extends Command
{
    protected $signature = 'hashguardian:tail
        {--type= : Filter by type (comma-separated: exception,log,request)}
        {--level= : Filter logs by level (error, warning, etc.)}
        {--interval=1 : Poll interval in seconds}';

    protected $description = 'Live tail HashGuardian entries in the terminal';

    protected bool $shouldStop = false;

    public function handle(): void
    {
        $types = $this->option('type') ? explode(',', $this->option('type')) : [];
        $level = $this->option('level');
        $interval = max(1, (int) $this->option('interval'));
        $connection = config('hashguardian.storage.database.connection', config('database.default'));

        $this->info('HashGuardian tail started. Press Ctrl+C to stop.');
        $this->info('Watching: ' . ($types ? implode(', ', $types) : 'all types'));
        $this->line(str_repeat('─', 80));

        $this->registerSignalHandlers();

        $lastId = DB::connection($connection)
            ->table('hashguardian_entries')
            ->max('id') ?? 0;

        while (! $this->shouldStop) {
            $query = DB::connection($connection)
                ->table('hashguardian_entries')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(50);

            if (! empty($types)) {
                $query->whereIn('type', $types);
            }

            $entries = $query->get();

            foreach ($entries as $entry) {
                $lastId = $entry->id;
                $content = is_string($entry->content) ? json_decode($entry->content, true) : (array) $entry->content;

                if ($level && $entry->type === EntryType::LOG) {
                    $entryLevel = $content['level'] ?? '';
                    if ($entryLevel !== $level) continue;
                }

                $this->outputEntry($entry, $content);
            }

            $this->sleepWithInterrupt($interval);
        }

        $this->newLine();
        $this->info('HashGuardian tail stopped.');
    }

    protected function outputEntry(object $entry, array $content): void
    {
        $time = \Carbon\Carbon::parse($entry->created_at)->format('H:i:s');
        $type = strtoupper($entry->type);

        $color = match ($entry->type) {
            EntryType::EXCEPTION => 'red',
            EntryType::LOG => match ($content['level'] ?? '') {
                'emergency', 'alert', 'critical', 'error' => 'red',
                'warning' => 'yellow',
                default => 'white',
            },
            EntryType::REQUEST => ((int) ($entry->status ?? 200)) >= 500 ? 'red' : (((int) ($entry->status ?? 200)) >= 400 ? 'yellow' : 'green'),
            EntryType::JOB => ($entry->status === 'failed') ? 'red' : 'green',
            default => 'white',
        };

        $summary = match ($entry->type) {
            EntryType::REQUEST => ($content['method'] ?? 'GET') . ' ' . ($content['uri'] ?? '/') . '  → ' . ($entry->status ?? '?') . '  ' . round($entry->duration ?? 0) . 'ms',
            EntryType::EXCEPTION => ($content['class'] ?? 'Exception') . '  "' . \Illuminate\Support\Str::limit($content['message'] ?? '', 60) . '"  ' . ($content['file'] ?? ''),
            EntryType::LOG => ($content['level'] ?? 'info') . '  "' . \Illuminate\Support\Str::limit($content['message'] ?? '', 60) . '"',
            EntryType::JOB => ($content['name'] ?? 'Job') . '  ' . ($entry->status ?? '') . '  ' . round($entry->duration ?? 0) . 'ms',
            EntryType::QUERY => \Illuminate\Support\Str::limit($content['sql'] ?? '', 60) . '  ' . round($entry->duration ?? 0) . 'ms',
            EntryType::CACHE => ($content['type'] ?? '') . '  ' . ($content['key'] ?? ''),
            default => json_encode(array_slice($content, 0, 3)),
        };

        $this->line("<fg={$color}>[{$time}] {$type}</> {$summary}");
    }

    protected function registerSignalHandlers(): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $this->shouldStop = true);
            pcntl_signal(SIGINT, fn () => $this->shouldStop = true);
        }
    }

    protected function sleepWithInterrupt(int $seconds): void
    {
        for ($i = 0; $i < $seconds * 10; $i++) {
            if ($this->shouldStop) return;
            usleep(100000);
        }
    }
}
