<?php

namespace Hashcrypttech\HashGuardian\Console;

use Illuminate\Console\Command;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\Storage\DatabaseEntriesRepository;

class PruneCommand extends Command
{
    protected $signature = 'hashguardian:prune {--hours= : Hours of data to retain}';

    protected $description = 'Prune stale entries from the HashGuardian database';

    public function handle(EntriesRepository $repository): void
    {
        $hours = $this->option('hours') ?? config('hashguardian.prune.hours', 72);

        $this->info("Pruning entries older than {$hours} hours...");

        $count = $repository->prune((int) $hours);

        $this->info("Pruned {$count} entries.");

        if ($repository instanceof DatabaseEntriesRepository) {
            $metricsHours = config('hashguardian.server_monitoring.prune_hours', 168);
            $metricsCount = $repository->pruneServerMetrics($metricsHours);
            $this->info("Pruned {$metricsCount} server metrics (older than {$metricsHours}h).");
        }

        if (config('hashguardian.aggregates.enabled', true)) {
            $retentionDays = config('hashguardian.aggregates.retention_days', 30);
            $aggregator = new \Hashcrypttech\HashGuardian\Aggregation\MetricsAggregator(
                config('hashguardian.storage.database.connection')
            );
            $aggPruned = $aggregator->pruneAggregates($retentionDays);
            if ($aggPruned > 0) {
                $this->info("Pruned {$aggPruned} stale hourly aggregates (>{$retentionDays}d).");
            }
        }
    }
}
