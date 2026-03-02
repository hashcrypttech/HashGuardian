<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\Storage\DatabaseEntriesRepository;

class StatsApiController extends Controller
{
    public function index(Request $request, EntriesRepository $repository): JsonResponse
    {
        $hours = (int) $request->input('hours', 1);
        $since = now()->subHours($hours);

        $counts = $repository->getCounts(['since' => $since]);
        $stats = $repository->getStats('request', ['since' => $since->toDateTimeString()]);

        $serverMetrics = null;
        if ($repository instanceof DatabaseEntriesRepository) {
            $latest = $repository->getServerMetricsLatest();
            if ($latest) {
                $serverMetrics = [
                    'cpu_percent' => round($latest->cpu_percent, 1),
                    'ram_percent' => round($latest->ram_percent, 1),
                    'disk_percent' => round($latest->disk_percent, 1),
                    'recorded_at' => $latest->created_at,
                ];
            }
        }

        return response()->json([
            'period_hours' => $hours,
            'counts' => $counts,
            'request_stats' => [
                'total' => $stats['total'] ?? 0,
                'avg_duration_ms' => round($stats['avg_duration'] ?? 0, 2),
                'max_duration_ms' => round($stats['max_duration'] ?? 0, 2),
            ],
            'server' => $serverMetrics,
        ]);
    }
}
