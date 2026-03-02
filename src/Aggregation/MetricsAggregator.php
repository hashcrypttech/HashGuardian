<?php

namespace Hashcrypttech\HashGuardian\Aggregation;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Hashcrypttech\HashGuardian\EntryType;

class MetricsAggregator
{
    protected string $connection;

    public function __construct(?string $connection = null)
    {
        $this->connection = $connection ?? config('hashguardian.storage.database.connection', config('database.default'));
    }

    protected function table(string $name = 'hashguardian_aggregates')
    {
        return DB::connection($this->connection)->table($name);
    }

    protected function entriesTable()
    {
        return DB::connection($this->connection)->table('hashguardian_entries');
    }

    protected function serverMetricsTable()
    {
        return DB::connection($this->connection)->table('hashguardian_server_metrics');
    }

    public function aggregateHourly(Carbon $hour): array
    {
        $start = $hour->copy()->startOfHour();
        $end = $start->copy()->addHour();
        $results = [];

        $results = array_merge($results, $this->aggregateRequests($start, $end, 'hourly'));
        $results = array_merge($results, $this->aggregateQueries($start, $end, 'hourly'));
        $results = array_merge($results, $this->aggregateJobs($start, $end, 'hourly'));
        $results = array_merge($results, $this->aggregateExceptions($start, $end, 'hourly'));
        $results = array_merge($results, $this->aggregateCache($start, $end, 'hourly'));
        $results = array_merge($results, $this->aggregateServerMetrics($start, $end, 'hourly'));

        $this->upsertAggregates($results);

        return $results;
    }

    public function rollup(string $fromPeriod, string $toPeriod, Carbon $before): int
    {
        $rows = $this->table()
            ->where('period', $fromPeriod)
            ->where('bucket', '<', $before)
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $grouped = $rows->groupBy(function ($row) use ($toPeriod) {
            $bucket = RollupStrategy::bucketStart(Carbon::parse($row->bucket), $toPeriod);
            return $row->type . '|' . $row->metric . '|' . ($row->dimension ?? '_') . '|' . $bucket->toDateTimeString();
        });

        $mergeRules = RollupStrategy::mergeable();
        $aggregates = [];

        foreach ($grouped as $key => $group) {
            [$type, $metric, $dimension, $bucketStr] = explode('|', $key, 4);
            $strategy = $mergeRules[$metric] ?? 'sum';

            $value = 0;
            $totalCount = 0;

            foreach ($group as $i => $row) {
                if ($i === 0) {
                    $value = (float) $row->value;
                    $totalCount = (int) $row->sample_count;
                } else {
                    $value = RollupStrategy::mergeValue(
                        $strategy, $value, $totalCount,
                        (float) $row->value, (int) $row->sample_count
                    );
                    $totalCount += (int) $row->sample_count;
                }
            }

            $aggregates[] = [
                'type' => $type,
                'metric' => $metric,
                'dimension' => $dimension === '_' ? null : $dimension,
                'period' => $toPeriod,
                'bucket' => $bucketStr,
                'value' => round($value, 4),
                'sample_count' => $totalCount,
            ];
        }

        $this->upsertAggregates($aggregates);

        return count($aggregates);
    }

    public function runRollups(): array
    {
        $counts = [];

        foreach (RollupStrategy::rollupThresholds() as $fromPeriod => $config) {
            $before = now()->subHours($config['after_hours']);
            $count = $this->rollup($fromPeriod, $config['target'], $before);
            $counts[$fromPeriod . '_to_' . $config['target']] = $count;
        }

        return $counts;
    }

    public function pruneAggregates(int $days): int
    {
        return $this->table()
            ->where('period', 'hourly')
            ->where('bucket', '<', now()->subDays($days))
            ->delete();
    }

    protected function aggregateRequests(Carbon $start, Carbon $end, string $period): array
    {
        $stats = $this->entriesTable()
            ->where('type', EntryType::REQUEST)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('COUNT(*) as count, AVG(duration) as avg_duration, MAX(duration) as max_duration')
            ->first();

        if (! $stats || $stats->count == 0) {
            return [];
        }

        $bucket = RollupStrategy::bucketStart($start, $period)->toDateTimeString();

        $p95 = $this->entriesTable()
            ->where('type', EntryType::REQUEST)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereNotNull('duration')
            ->orderBy('duration')
            ->offset((int) floor($stats->count * 0.95) - 1)
            ->limit(1)
            ->value('duration') ?? $stats->max_duration;

        $errorCount = $this->entriesTable()
            ->where('type', EntryType::REQUEST)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->where(function ($q) {
                $q->where('status', 'like', '4%')
                  ->orWhere('status', 'like', '5%');
            })
            ->count();

        $errorRate = $stats->count > 0 ? ($errorCount / $stats->count) * 100 : 0;

        $minutes = $start->diffInMinutes($end);
        $throughput = $minutes > 0 ? $stats->count / $minutes : 0;

        return [
            ['type' => 'request', 'metric' => 'count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $stats->count, 'sample_count' => $stats->count],
            ['type' => 'request', 'metric' => 'avg_duration', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($stats->avg_duration ?? 0, 4), 'sample_count' => $stats->count],
            ['type' => 'request', 'metric' => 'p95_duration', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($p95 ?? 0, 4), 'sample_count' => $stats->count],
            ['type' => 'request', 'metric' => 'error_rate', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($errorRate, 4), 'sample_count' => $stats->count],
            ['type' => 'request', 'metric' => 'throughput', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($throughput, 4), 'sample_count' => $stats->count],
        ];
    }

    protected function aggregateQueries(Carbon $start, Carbon $end, string $period): array
    {
        $stats = $this->entriesTable()
            ->where('type', EntryType::QUERY)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('COUNT(*) as count, AVG(duration) as avg_duration')
            ->first();

        if (! $stats || $stats->count == 0) {
            return [];
        }

        $bucket = RollupStrategy::bucketStart($start, $period)->toDateTimeString();

        $slowThreshold = config('hashguardian.watchers.' . \Hashcrypttech\HashGuardian\Watchers\QueryWatcher::class . '.slow', 100);
        $slowCount = $this->entriesTable()
            ->where('type', EntryType::QUERY)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->where('duration', '>=', $slowThreshold)
            ->count();

        return [
            ['type' => 'query', 'metric' => 'count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $stats->count, 'sample_count' => $stats->count],
            ['type' => 'query', 'metric' => 'avg_duration', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($stats->avg_duration ?? 0, 4), 'sample_count' => $stats->count],
            ['type' => 'query', 'metric' => 'slow_count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $slowCount, 'sample_count' => $stats->count],
        ];
    }

    protected function aggregateJobs(Carbon $start, Carbon $end, string $period): array
    {
        $stats = $this->entriesTable()
            ->where('type', EntryType::JOB)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->selectRaw('COUNT(*) as count, AVG(duration) as avg_duration')
            ->first();

        if (! $stats || $stats->count == 0) {
            return [];
        }

        $bucket = RollupStrategy::bucketStart($start, $period)->toDateTimeString();

        $failedCount = $this->entriesTable()
            ->where('type', EntryType::JOB)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->where('status', 'failed')
            ->count();

        $failRate = $stats->count > 0 ? ($failedCount / $stats->count) * 100 : 0;

        return [
            ['type' => 'job', 'metric' => 'count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $stats->count, 'sample_count' => $stats->count],
            ['type' => 'job', 'metric' => 'avg_duration', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($stats->avg_duration ?? 0, 4), 'sample_count' => $stats->count],
            ['type' => 'job', 'metric' => 'fail_rate', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($failRate, 4), 'sample_count' => $stats->count],
        ];
    }

    protected function aggregateExceptions(Carbon $start, Carbon $end, string $period): array
    {
        $count = $this->entriesTable()
            ->where('type', EntryType::EXCEPTION)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();

        if ($count == 0) {
            return [];
        }

        $bucket = RollupStrategy::bucketStart($start, $period)->toDateTimeString();

        $uniqueCount = $this->entriesTable()
            ->where('type', EntryType::EXCEPTION)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->distinct('family_hash')
            ->count('family_hash');

        return [
            ['type' => 'exception', 'metric' => 'count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $count, 'sample_count' => $count],
            ['type' => 'exception', 'metric' => 'unique_count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $uniqueCount, 'sample_count' => $count],
        ];
    }

    protected function aggregateCache(Carbon $start, Carbon $end, string $period): array
    {
        $entries = $this->entriesTable()
            ->where('type', EntryType::CACHE)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->select('content')
            ->get();

        if ($entries->isEmpty()) {
            return [];
        }

        $bucket = RollupStrategy::bucketStart($start, $period)->toDateTimeString();
        $hitCount = 0;
        $missCount = 0;

        foreach ($entries as $entry) {
            $content = is_string($entry->content) ? json_decode($entry->content, true) : (array) $entry->content;
            $type = $content['type'] ?? '';
            if ($type === 'hit') $hitCount++;
            elseif ($type === 'missed') $missCount++;
        }

        $total = $hitCount + $missCount;
        $hitRatio = $total > 0 ? ($hitCount / $total) * 100 : 0;

        return [
            ['type' => 'cache', 'metric' => 'hit_count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $hitCount, 'sample_count' => $total],
            ['type' => 'cache', 'metric' => 'miss_count', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => $missCount, 'sample_count' => $total],
            ['type' => 'cache', 'metric' => 'hit_ratio', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($hitRatio, 4), 'sample_count' => $total],
        ];
    }

    protected function aggregateServerMetrics(Carbon $start, Carbon $end, string $period): array
    {
        try {
            $stats = $this->serverMetricsTable()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->selectRaw('AVG(cpu_percent) as avg_cpu, MAX(cpu_percent) as peak_cpu, AVG(ram_percent) as avg_memory, MAX(ram_percent) as peak_memory, COUNT(*) as count')
                ->first();
        } catch (\Throwable $e) {
            return [];
        }

        if (! $stats || $stats->count == 0) {
            return [];
        }

        $bucket = RollupStrategy::bucketStart($start, $period)->toDateTimeString();

        return [
            ['type' => 'server', 'metric' => 'avg_cpu', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($stats->avg_cpu, 4), 'sample_count' => $stats->count],
            ['type' => 'server', 'metric' => 'peak_cpu', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($stats->peak_cpu, 4), 'sample_count' => $stats->count],
            ['type' => 'server', 'metric' => 'avg_memory', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($stats->avg_memory, 4), 'sample_count' => $stats->count],
            ['type' => 'server', 'metric' => 'peak_memory', 'dimension' => null, 'period' => $period, 'bucket' => $bucket, 'value' => round($stats->peak_memory, 4), 'sample_count' => $stats->count],
        ];
    }

    protected function upsertAggregates(array $aggregates): void
    {
        if (empty($aggregates)) {
            return;
        }

        foreach ($aggregates as $agg) {
            $existing = $this->table()
                ->where('type', $agg['type'])
                ->where('metric', $agg['metric'])
                ->where('dimension', $agg['dimension'])
                ->where('period', $agg['period'])
                ->where('bucket', $agg['bucket'])
                ->first();

            if ($existing) {
                $this->table()->where('id', $existing->id)->update([
                    'value' => $agg['value'],
                    'sample_count' => $agg['sample_count'],
                    'updated_at' => now(),
                ]);
            } else {
                $this->table()->insert(array_merge($agg, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function getAggregates(string $type, string $metric, string $period, Carbon $from, Carbon $to, ?string $dimension = null): Collection
    {
        $query = $this->table()
            ->where('type', $type)
            ->where('metric', $metric)
            ->where('period', $period)
            ->where('bucket', '>=', $from)
            ->where('bucket', '<=', $to)
            ->orderBy('bucket');

        if ($dimension !== null) {
            $query->where('dimension', $dimension);
        } else {
            $query->whereNull('dimension');
        }

        return $query->get();
    }

    public function getLatestAggregates(string $period, Carbon $from, Carbon $to): Collection
    {
        return $this->table()
            ->where('period', $period)
            ->where('bucket', '>=', $from)
            ->where('bucket', '<=', $to)
            ->whereNull('dimension')
            ->orderBy('bucket')
            ->get();
    }

    public function getSlowestEndpoints(string $period, Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return $this->table()
            ->where('type', 'request')
            ->where('metric', 'p95_duration')
            ->where('period', $period)
            ->where('bucket', '>=', $from)
            ->where('bucket', '<=', $to)
            ->whereNotNull('dimension')
            ->orderByDesc('value')
            ->limit($limit)
            ->get();
    }
}
