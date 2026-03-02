<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Hashcrypttech\HashGuardian\Aggregation\MetricsAggregator;

class TrendsController extends Controller
{
    protected MetricsAggregator $aggregator;

    public function __construct()
    {
        $this->aggregator = new MetricsAggregator(
            config('hashguardian.storage.database.connection')
        );
    }

    public function index()
    {
        return view('hashguardian::trends.index');
    }

    public function chartData(Request $request): JsonResponse
    {
        $period = $request->input('period', '24h');
        [$from, $to, $granularity] = $this->parsePeriod($period, $request);

        $aggregates = $this->aggregator->getLatestAggregates($granularity, $from, $to);

        $grouped = $aggregates->groupBy('bucket');
        $labels = [];
        $datasets = [
            'request_count' => [],
            'request_avg_duration' => [],
            'request_p95_duration' => [],
            'request_error_rate' => [],
            'request_throughput' => [],
            'query_count' => [],
            'query_avg_duration' => [],
            'query_slow_count' => [],
            'job_count' => [],
            'job_fail_rate' => [],
            'exception_count' => [],
            'cache_hit_ratio' => [],
            'server_avg_cpu' => [],
            'server_avg_memory' => [],
        ];

        foreach ($grouped as $bucket => $rows) {
            $labels[] = $bucket;
            $byKey = $rows->keyBy(fn ($r) => $r->type . '_' . $r->metric);

            foreach ($datasets as $key => &$values) {
                $values[] = isset($byKey[$key]) ? round((float) $byKey[$key]->value, 2) : 0;
            }
        }

        $prevFrom = $from->copy()->sub($from->diff($to));
        $prevAggregates = $this->aggregator->getLatestAggregates($granularity, $prevFrom, $from);

        $summary = $this->buildSummary($aggregates, $prevAggregates);

        return response()->json([
            'labels' => $labels,
            'datasets' => $datasets,
            'summary' => $summary,
            'granularity' => $granularity,
        ]);
    }

    public function comparison(Request $request): JsonResponse
    {
        $period = $request->input('period', '24h');
        [$currentFrom, $currentTo, $granularity] = $this->parsePeriod($period, $request);

        $prevDuration = $currentFrom->diff($currentTo);
        $prevFrom = $currentFrom->copy()->sub($prevDuration);
        $prevTo = $currentFrom->copy();

        $currentData = $this->aggregator->getLatestAggregates($granularity, $currentFrom, $currentTo);
        $previousData = $this->aggregator->getLatestAggregates($granularity, $prevFrom, $prevTo);

        return response()->json([
            'current' => [
                'from' => $currentFrom->toDateTimeString(),
                'to' => $currentTo->toDateTimeString(),
                'data' => $this->flattenAggregates($currentData),
            ],
            'previous' => [
                'from' => $prevFrom->toDateTimeString(),
                'to' => $prevTo->toDateTimeString(),
                'data' => $this->flattenAggregates($previousData),
            ],
        ]);
    }

    public function slowest(Request $request): JsonResponse
    {
        $period = $request->input('period', '24h');
        [$from, $to, $granularity] = $this->parsePeriod($period, $request);

        $slowest = $this->aggregator->getSlowestEndpoints($granularity, $from, $to, 10);

        $results = $slowest->map(fn ($row) => [
            'endpoint' => $row->dimension,
            'p95_duration' => round((float) $row->value, 2),
            'sample_count' => $row->sample_count,
            'bucket' => $row->bucket,
        ]);

        return response()->json($results->values());
    }

    protected function parsePeriod(string $period, Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'));
            $to = Carbon::parse($request->input('to'));
        } else {
            $to = now();
            $from = match ($period) {
                '1h' => $to->copy()->subHour(),
                '3h' => $to->copy()->subHours(3),
                '6h' => $to->copy()->subHours(6),
                '24h' => $to->copy()->subDay(),
                '7d' => $to->copy()->subWeek(),
                '30d' => $to->copy()->subMonth(),
                '90d' => $to->copy()->subMonths(3),
                default => $to->copy()->subDay(),
            };
        }

        $hours = $from->diffInHours($to);
        $granularity = match (true) {
            $hours <= 48 => 'hourly',
            $hours <= 720 => 'daily',
            $hours <= 2160 => 'weekly',
            default => 'monthly',
        };

        return [$from, $to, $granularity];
    }

    protected function buildSummary($currentData, $previousData): array
    {
        $currentMetrics = $this->summarizeMetrics($currentData);
        $previousMetrics = $this->summarizeMetrics($previousData);

        $summary = [];
        foreach ($currentMetrics as $key => $value) {
            $prev = $previousMetrics[$key] ?? 0;
            $delta = $prev > 0 ? (($value - $prev) / $prev) * 100 : ($value > 0 ? 100 : 0);

            $summary[$key] = [
                'value' => round($value, 2),
                'previous' => round($prev, 2),
                'delta' => round($delta, 1),
            ];
        }

        return $summary;
    }

    protected function summarizeMetrics($aggregates): array
    {
        $metrics = [];
        $counts = [];

        foreach ($aggregates as $row) {
            $key = $row->type . '_' . $row->metric;

            if (in_array($row->metric, ['count', 'hit_count', 'miss_count', 'slow_count'])) {
                $metrics[$key] = ($metrics[$key] ?? 0) + (float) $row->value;
            } else {
                $metrics[$key] = ($metrics[$key] ?? 0) + ((float) $row->value * $row->sample_count);
                $counts[$key] = ($counts[$key] ?? 0) + $row->sample_count;
            }
        }

        foreach ($counts as $key => $count) {
            if ($count > 0) {
                $metrics[$key] = $metrics[$key] / $count;
            }
        }

        return $metrics;
    }

    protected function flattenAggregates($aggregates): array
    {
        $result = [];
        foreach ($aggregates as $row) {
            $result[] = [
                'type' => $row->type,
                'metric' => $row->metric,
                'bucket' => $row->bucket,
                'value' => round((float) $row->value, 2),
                'sample_count' => $row->sample_count,
            ];
        }
        return $result;
    }
}
