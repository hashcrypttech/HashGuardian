<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Hashcrypttech\HashGuardian\Aggregation\MetricsAggregator;

class AggregateCommand extends Command
{
    protected $signature = 'hashguardian:aggregate
        {--period=hourly : Period to aggregate (hourly)}
        {--hours=1 : How many hours back to aggregate}
        {--rollup : Also run rollups (hourly→daily→weekly→monthly)}';

    protected $description = 'Aggregate raw HashGuardian entries into metric summaries';

    public function handle(): void
    {
        $connection = config('hashguardian.storage.database.connection');
        $aggregator = new MetricsAggregator($connection);

        $hours = (int) $this->option('hours');

        $this->info("Aggregating entries for the last {$hours} hour(s)...");

        $totalResults = 0;

        for ($i = $hours; $i >= 1; $i--) {
            $hour = Carbon::now()->subHours($i)->startOfHour();
            $results = $aggregator->aggregateHourly($hour);
            $totalResults += count($results);

            if ($this->getOutput()->isVerbose()) {
                $this->line("  [{$hour->format('Y-m-d H:00')}] " . count($results) . " metrics aggregated");
            }
        }

        $this->info("Aggregated {$totalResults} metric rows.");

        if ($this->option('rollup')) {
            $this->info('Running rollups...');
            $counts = $aggregator->runRollups();

            foreach ($counts as $transition => $count) {
                $this->info("  {$transition}: {$count} rows rolled up");
            }
        }

        $retentionDays = config('hashguardian.aggregates.retention_days', 30);
        $pruned = $aggregator->pruneAggregates($retentionDays);
        if ($pruned > 0) {
            $this->info("Pruned {$pruned} stale hourly aggregates (>{$retentionDays}d).");
        }
    }
}
