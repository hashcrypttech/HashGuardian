<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Storage\DatabaseEntriesRepository;

class ServerController extends Controller
{
    protected DatabaseEntriesRepository $repository;

    public function __construct(DatabaseEntriesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $latest = $this->repository->getServerMetricsLatest();
        $monitoringEnabled = config('hashguardian.server_monitoring.enabled', false);

        return view('hashguardian::server.index', [
            'latest' => $latest,
            'monitoringEnabled' => $monitoringEnabled,
            'interval' => config('hashguardian.server_monitoring.interval', 15),
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $period = $request->input('period', '3h');
        $since = $request->input('since');
        $until = $request->input('until');

        $metrics = $this->repository->getServerMetricsChart($period, $since, $until);

        $data = [
            'labels' => [],
            'cpu' => [],
            'ram' => [],
            'swap' => [],
            'disk' => [],
            'net_in' => [],
            'net_out' => [],
            'load_1m' => [],
            'processes' => [],
        ];

        foreach ($metrics as $m) {
            $data['labels'][] = $m->created_at;
            $data['cpu'][] = round($m->cpu_percent, 1);
            $data['ram'][] = round($m->ram_percent, 1);
            $data['swap'][] = round($m->swap_percent, 1);
            $data['disk'][] = round($m->disk_percent, 1);
            $data['net_in'][] = $m->net_bytes_in;
            $data['net_out'][] = $m->net_bytes_out;
            $data['load_1m'][] = round($m->cpu_load_1m, 2);
            $data['processes'][] = $m->process_count;
        }

        if (! empty($data['labels'])) {
            $interval = config('hashguardian.server_monitoring.interval', 15);
            $firstLabel = $data['labels'][0];
            $lastLabel = end($data['labels']);

            $data['activity'] = $this->repository->getActivityCounts(
                $firstLabel, $lastLabel, $data['labels'], $interval
            );

            $data['queries'] = $this->repository->getQueryActivityCounts(
                $firstLabel, $lastLabel, $data['labels'], $interval
            );
        } else {
            $data['activity'] = ['requests' => [], 'jobs' => [], 'commands' => []];
            $data['queries'] = ['total' => [], 'select' => [], 'insert' => [], 'update' => [], 'delete' => []];
        }

        return response()->json($data);
    }

    public function correlate(Request $request): JsonResponse
    {
        $since = $request->input('since', now()->subMinutes(5)->toDateTimeString());
        $until = $request->input('until', now()->toDateTimeString());
        $sortBy = $request->input('sort', 'duration');

        $entries = $this->repository->getHighResourceEntries($since, $until, $sortBy);

        $grouped = ['requests' => [], 'jobs' => [], 'commands' => []];

        foreach ($entries as $entry) {
            $item = [
                'uuid' => $entry->uuid,
                'type' => $entry->type,
                'created_at' => $entry->created_at,
                'duration' => $entry->duration,
                'status' => $entry->status,
                'name' => $this->getEntryName($entry),
            ];

            match ($entry->type) {
                'request' => $grouped['requests'][] = $item,
                'job' => $grouped['jobs'][] = $item,
                'command' => $grouped['commands'][] = $item,
                default => null,
            };
        }

        return response()->json([
            'requests' => $grouped['requests'],
            'jobs' => $grouped['jobs'],
            'commands' => $grouped['commands'],
            'counts' => [
                'requests' => count($grouped['requests']),
                'jobs' => count($grouped['jobs']),
                'commands' => count($grouped['commands']),
            ],
        ]);
    }

    protected function getEntryName(object $entry): string
    {
        $content = $entry->content ?? [];

        if ($entry->type === 'request') {
            return ($content['method'] ?? 'GET') . ' ' . ($content['uri'] ?? '/');
        }

        if ($entry->type === 'job') {
            $name = $content['name'] ?? $content['job'] ?? 'Unknown';
            return class_basename($name);
        }

        if ($entry->type === 'command') {
            return $content['command'] ?? $content['name'] ?? 'Unknown';
        }

        return $content['name'] ?? $entry->type;
    }
}
