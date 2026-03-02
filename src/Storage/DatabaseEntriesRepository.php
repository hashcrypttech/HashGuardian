<?php

namespace Hashcrypttech\HashGuardian\Storage;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class DatabaseEntriesRepository implements EntriesRepository
{
    protected string $connection;

    protected int $chunkSize;

    public function __construct(?string $connection = null, int $chunkSize = 1000)
    {
        $this->connection = $connection ?? config('database.default');
        $this->chunkSize = $chunkSize;
    }

    protected function table(string $name = 'hashguardian_entries')
    {
        return DB::connection($this->connection)->table($name);
    }

    protected function isPostgres(): bool
    {
        return DB::connection($this->connection)->getDriverName() === 'pgsql';
    }

    public function store(array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        $entryRows = [];
        $tagRows = [];

        foreach ($entries as $entry) {
            /** @var IncomingEntry $entry */
            $entryRows[] = $entry->toArray();

            foreach ($entry->tags as $tag) {
                $tagRows[] = [
                    'entry_uuid' => $entry->uuid,
                    'tag' => $tag,
                ];
            }
        }

        foreach (array_chunk($entryRows, $this->chunkSize) as $chunk) {
            $this->table()->insert($chunk);
        }

        if (! empty($tagRows)) {
            foreach (array_chunk($tagRows, $this->chunkSize) as $chunk) {
                $this->table('hashguardian_entries_tags')->insert($chunk);
            }
        }
    }

    public function find(string $uuid): ?object
    {
        $entry = $this->table()->where('uuid', $uuid)->first();

        if ($entry) {
            $entry->content = json_decode($entry->content, true);
            $entry->tags = $this->table('hashguardian_entries_tags')
                ->where('entry_uuid', $uuid)
                ->pluck('tag')
                ->toArray();
        }

        return $entry;
    }

    public function get(string $type, array $options = []): Collection
    {
        $query = $this->table()
            ->where('type', $type)
            ->orderByDesc('created_at');

        if (isset($options['before'])) {
            $query->where('id', '<', $options['before']);
        }

        if (isset($options['tag'])) {
            $query->whereIn('uuid', function ($q) use ($options) {
                $q->select('entry_uuid')
                    ->from('hashguardian_entries_tags')
                    ->where('tag', $options['tag']);
            });
        }

        if (isset($options['family_hash'])) {
            $query->where('family_hash', $options['family_hash']);
        }

        if (isset($options['since'])) {
            $query->where('created_at', '>=', $options['since']);
        }

        if (isset($options['until'])) {
            $query->where('created_at', '<=', $options['until']);
        }

        if (isset($options['status'])) {
            $query->where('status', $options['status']);
        }

        if (isset($options['status_group'])) {
            $group = (int) $options['status_group'];
            $castType = $this->isPostgres() ? 'INTEGER' : 'SIGNED';
            $query->whereRaw("CAST(status AS {$castType}) >= ? AND CAST(status AS {$castType}) < ?", [$group, $group + 100]);
        }

        if (isset($options['min_duration'])) {
            $query->where('duration', '>=', (float) $options['min_duration']);
        }

        $limit = $options['limit'] ?? 50;

        return $query->limit($limit)->get()->map(function ($entry) {
            $entry->content = json_decode($entry->content, true);
            return $entry;
        });
    }

    public function getByBatchId(string $batchId): Collection
    {
        return $this->table()
            ->where('batch_id', $batchId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($entry) {
                $entry->content = json_decode($entry->content, true);
                return $entry;
            });
    }

    public function getStats(string $type, array $options = []): array
    {
        $query = $this->table()->where('type', $type);

        if (isset($options['since'])) {
            $query->where('created_at', '>=', $options['since']);
        }

        if (isset($options['until'])) {
            $query->where('created_at', '<=', $options['until']);
        }

        $result = $query->selectRaw('
            COUNT(*) as total,
            AVG(duration) as avg_duration,
            MAX(duration) as max_duration,
            MIN(duration) as min_duration
        ')->first();

        return (array) $result;
    }

    public function getCounts(array $options = []): array
    {
        $query = $this->table();

        if (isset($options['since'])) {
            $query->where('created_at', '>=', $options['since']);
        }

        return $query->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    public function prune(int $hours): int
    {
        $before = Carbon::now()->subHours($hours);

        $uuids = $this->table()
            ->where('created_at', '<', $before)
            ->pluck('uuid');

        if ($uuids->isEmpty()) {
            return 0;
        }

        foreach ($uuids->chunk($this->chunkSize) as $chunk) {
            $this->table('hashguardian_entries_tags')
                ->whereIn('entry_uuid', $chunk->toArray())
                ->delete();
        }

        return $this->table()
            ->where('created_at', '<', $before)
            ->delete();
    }

    public function clear(): void
    {
        $this->table('hashguardian_entries_tags')->truncate();
        $this->table('hashguardian_monitoring')->truncate();
        $this->table()->truncate();

        try {
            $this->table('hashguardian_server_metrics')->truncate();
        } catch (\Throwable $e) {
            // Table may not exist yet
        }
    }

    public function getServerMetrics(array $options = []): Collection
    {
        $query = $this->table('hashguardian_server_metrics')
            ->orderByDesc('created_at');

        if (isset($options['since'])) {
            $query->where('created_at', '>=', $options['since']);
        }

        if (isset($options['until'])) {
            $query->where('created_at', '<=', $options['until']);
        }

        $limit = $options['limit'] ?? 500;

        return $query->limit($limit)->get();
    }

    public function getServerMetricsLatest(): ?object
    {
        return $this->table('hashguardian_server_metrics')
            ->orderByDesc('created_at')
            ->first();
    }

    public function getServerMetricsChart(string $period = '1h', ?string $since = null, ?string $until = null): Collection
    {
        $query = $this->table('hashguardian_server_metrics')
            ->orderBy('created_at');

        if ($since) {
            $query->where('created_at', '>=', $since);
        } elseif ($period) {
            $hours = match ($period) {
                '1h' => 1,
                '3h' => 3,
                '6h' => 6,
                '24h' => 24,
                '7d' => 168,
                '30d' => 720,
                default => 3,
            };
            $query->where('created_at', '>=', now()->subHours($hours));
        }

        if ($until) {
            $query->where('created_at', '<=', $until);
        }

        return $query->get();
    }

    public function getActivityCounts(string $since, string $until, array $bucketTimestamps, int $intervalSeconds = 15): array
    {
        $types = [EntryType::REQUEST, EntryType::JOB, EntryType::COMMAND];

        $rows = $this->table()
            ->whereIn('type', $types)
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $until)
            ->select(['type', 'created_at'])
            ->orderBy('created_at')
            ->get();

        $halfInterval = $intervalSeconds / 2;
        $result = ['requests' => [], 'jobs' => [], 'commands' => []];

        foreach ($bucketTimestamps as $ts) {
            $bucketTime = strtotime($ts);
            $bucketStart = $bucketTime - $halfInterval;
            $bucketEnd = $bucketTime + $halfInterval;

            $counts = ['request' => 0, 'job' => 0, 'command' => 0];

            foreach ($rows as $row) {
                $rowTime = strtotime($row->created_at);
                if ($rowTime >= $bucketStart && $rowTime < $bucketEnd) {
                    if (isset($counts[$row->type])) {
                        $counts[$row->type]++;
                    }
                }
            }

            $result['requests'][] = $counts['request'];
            $result['jobs'][] = $counts['job'];
            $result['commands'][] = $counts['command'];
        }

        return $result;
    }

    public function getQueryActivityCounts(string $since, string $until, array $bucketTimestamps, int $intervalSeconds = 15): array
    {
        $rows = $this->table()
            ->where('type', EntryType::QUERY)
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $until)
            ->select(['content', 'created_at'])
            ->orderBy('created_at')
            ->get();

        $halfInterval = $intervalSeconds / 2;
        $result = ['total' => [], 'select' => [], 'insert' => [], 'update' => [], 'delete' => []];

        foreach ($bucketTimestamps as $ts) {
            $bucketTime = strtotime($ts);
            $bucketStart = $bucketTime - $halfInterval;
            $bucketEnd = $bucketTime + $halfInterval;

            $counts = ['total' => 0, 'select' => 0, 'insert' => 0, 'update' => 0, 'delete' => 0];

            foreach ($rows as $row) {
                $rowTime = strtotime($row->created_at);
                if ($rowTime >= $bucketStart && $rowTime < $bucketEnd) {
                    $counts['total']++;
                    $content = is_string($row->content) ? json_decode($row->content, true) : (array) $row->content;
                    $sql = ltrim($content['sql'] ?? '');
                    if (stripos($sql, 'select') === 0) $counts['select']++;
                    elseif (stripos($sql, 'insert') === 0) $counts['insert']++;
                    elseif (stripos($sql, 'update') === 0) $counts['update']++;
                    elseif (stripos($sql, 'delete') === 0) $counts['delete']++;
                }
            }

            $result['total'][] = $counts['total'];
            $result['select'][] = $counts['select'];
            $result['insert'][] = $counts['insert'];
            $result['update'][] = $counts['update'];
            $result['delete'][] = $counts['delete'];
        }

        return $result;
    }

    public function getHighResourceEntries(string $since, string $until, string $sortBy = 'memory'): Collection
    {
        $query = $this->table()
            ->whereIn('type', [EntryType::REQUEST, EntryType::JOB, EntryType::COMMAND])
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $until)
            ->limit(50);

        if ($sortBy === 'duration') {
            $query->orderByDesc('duration');
        } else {
            $query->orderBy('created_at');
        }

        return $query->get()->map(function ($entry) {
            $entry->content = json_decode($entry->content, true);
            return $entry;
        });
    }


    public function getStatusCodeCounts(string $type, array $options = []): array
    {
        $query = $this->table()
            ->selectRaw('status, COUNT(*) as count')
            ->where('type', $type)
            ->groupBy('status')
            ->orderBy('status');

        if (isset($options['since'])) {
            $query->where('created_at', '>=', $options['since']);
        }
        if (isset($options['until'])) {
            $query->where('created_at', '<=', $options['until']);
        }

        return $query->pluck('count', 'status')->toArray();
    }

    public function getRequestsTimeline(string $type, array $options = []): array
    {
        $since = $options['since'] ?? now()->subHours(24)->toDateTimeString();
        $until = $options['until'] ?? now()->toDateTimeString();

        $start = Carbon::parse($since);
        $end = Carbon::parse($until);
        $diffMinutes = $start->diffInMinutes($end);

        if ($diffMinutes <= 120) {
            $interval = 5;
        } elseif ($diffMinutes <= 720) {
            $interval = 15;
        } elseif ($diffMinutes <= 1440) {
            $interval = 30;
        } else {
            $interval = 60;
        }

        $buckets = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $buckets[] = $cursor->format('Y-m-d H:i:s');
            $cursor->addMinutes($interval);
        }

        $entries = $this->table()
            ->where('type', $type)
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $until)
            ->get(['status', 'duration', 'created_at']);

        $timeline = [];
        foreach ($buckets as $i => $bucket) {
            $bucketEnd = isset($buckets[$i + 1]) ? $buckets[$i + 1] : $end->format('Y-m-d H:i:s');
            $timeline[] = [
                'time' => $bucket,
                '2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0,
                'avg_duration' => 0, 'count' => 0, 'total_duration' => 0,
            ];
        }

        foreach ($entries as $entry) {
            $t = strtotime($entry->created_at);
            for ($i = count($buckets) - 1; $i >= 0; $i--) {
                if ($t >= strtotime($buckets[$i])) {
                    $code = (int) $entry->status;
                    if ($code >= 200 && $code < 300) $timeline[$i]['2xx']++;
                    elseif ($code >= 300 && $code < 400) $timeline[$i]['3xx']++;
                    elseif ($code >= 400 && $code < 500) $timeline[$i]['4xx']++;
                    elseif ($code >= 500) $timeline[$i]['5xx']++;
                    $timeline[$i]['count']++;
                    $timeline[$i]['total_duration'] += (float) ($entry->duration ?? 0);
                    break;
                }
            }
        }

        foreach ($timeline as &$t) {
            $t['avg_duration'] = $t['count'] > 0 ? round($t['total_duration'] / $t['count'], 1) : 0;
            unset($t['total_duration']);
        }

        return $timeline;
    }

    public function pruneServerMetrics(int $hours): int
    {
        return $this->table('hashguardian_server_metrics')
            ->where('created_at', '<', Carbon::now()->subHours($hours))
            ->delete();
    }
}
