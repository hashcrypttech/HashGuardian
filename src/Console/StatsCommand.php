<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class StatsCommand extends Command
{
    protected $signature = 'hashguardian:stats {--period=1h : Time period (1h, 6h, 24h, 7d)}';

    protected $description = 'Show a quick health snapshot from HashGuardian';

    public function handle(EntriesRepository $repository): void
    {
        $hours = match ($this->option('period')) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            default => 1,
        };

        $since = now()->subHours($hours);
        $this->info("HashGuardian Stats (last {$this->option('period')})");
        $this->info(str_repeat('─', 50));

        $counts = $repository->getCounts(['since' => $since]);
        $requestStats = $repository->getStats(EntryType::REQUEST, ['since' => $since->toDateTimeString()]);

        $this->table(['Metric', 'Value'], [
            ['Requests', number_format($counts[EntryType::REQUEST] ?? 0)],
            ['Queries', number_format($counts[EntryType::QUERY] ?? 0)],
            ['Exceptions', number_format($counts[EntryType::EXCEPTION] ?? 0)],
            ['Jobs', number_format($counts[EntryType::JOB] ?? 0)],
            ['Avg Duration', number_format($requestStats['avg_duration'] ?? 0, 1) . 'ms'],
            ['Max Duration', number_format($requestStats['max_duration'] ?? 0, 1) . 'ms'],
            ['Cache Events', number_format($counts[EntryType::CACHE] ?? 0)],
            ['Mail', number_format($counts[EntryType::MAIL] ?? 0)],
            ['Commands', number_format($counts[EntryType::COMMAND] ?? 0)],
            ['Logs', number_format($counts[EntryType::LOG] ?? 0)],
        ]);

        $recentExceptions = $repository->get(EntryType::EXCEPTION, [
            'limit' => 3,
            'since' => $since,
        ]);

        if ($recentExceptions->isNotEmpty()) {
            $this->newLine();
            $this->warn('Recent Exceptions:');
            foreach ($recentExceptions as $e) {
                $class = $e->content['class'] ?? 'Unknown';
                $msg = \Illuminate\Support\Str::limit($e->content['message'] ?? '', 60);
                $time = \Carbon\Carbon::parse($e->created_at)->diffForHumans();
                $this->line("  [{$time}] {$class}: {$msg}");
            }
        }
    }
}
