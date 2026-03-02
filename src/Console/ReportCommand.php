<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class ReportCommand extends Command
{
    protected $signature = 'hashguardian:report
        {--from= : Start date (Y-m-d or Y-m-d H:i:s)}
        {--to= : End date (defaults to now)}
        {--format=text : Output format (text, json)}';

    protected $description = 'Generate a HashGuardian summary report';

    public function handle(EntriesRepository $repository): void
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : now()->subDay();
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : now();
        $format = $this->option('format');

        $counts = $repository->getCounts([
            'since' => $from,
            'until' => $to,
        ]);

        $requestStats = $repository->getStats(EntryType::REQUEST, [
            'since' => $from->toDateTimeString(),
            'until' => $to->toDateTimeString(),
        ]);

        $exceptions = $repository->get(EntryType::EXCEPTION, [
            'since' => $from,
            'until' => $to,
            'limit' => 10,
        ]);

        $report = [
            'period' => [
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
            ],
            'counts' => $counts,
            'request_stats' => [
                'total' => $requestStats['total'] ?? 0,
                'avg_duration_ms' => round($requestStats['avg_duration'] ?? 0, 2),
                'max_duration_ms' => round($requestStats['max_duration'] ?? 0, 2),
                'min_duration_ms' => round($requestStats['min_duration'] ?? 0, 2),
            ],
            'top_exceptions' => $exceptions->map(fn ($e) => [
                'class' => $e->content['class'] ?? 'Unknown',
                'message' => \Illuminate\Support\Str::limit($e->content['message'] ?? '', 100),
                'time' => $e->created_at,
            ])->values()->toArray(),
        ];

        if ($format === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return;
        }

        $this->info('═══════════════════════════════════════════');
        $this->info('        HASHGUARDIAN REPORT');
        $this->info('═══════════════════════════════════════════');
        $this->info("Period: {$from->toDateTimeString()} → {$to->toDateTimeString()}");
        $this->newLine();

        $this->table(['Type', 'Count'], collect($counts)->map(fn ($v, $k) => [$k, number_format($v)])->values()->toArray());

        $this->newLine();
        $this->info('Request Performance:');
        $this->line("  Total: {$report['request_stats']['total']}");
        $this->line("  Avg Duration: {$report['request_stats']['avg_duration_ms']}ms");
        $this->line("  Max Duration: {$report['request_stats']['max_duration_ms']}ms");

        if ($exceptions->isNotEmpty()) {
            $this->newLine();
            $this->warn('Top Exceptions:');
            foreach ($exceptions->take(5) as $e) {
                $class = $e->content['class'] ?? 'Unknown';
                $msg = \Illuminate\Support\Str::limit($e->content['message'] ?? '', 50);
                $this->line("  • {$class}: {$msg}");
            }
        }
    }
}
