<?php

namespace Hashcrypttech\HashGuardian\Reports;

use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class DigestReportGenerator
{
    protected EntriesRepository $repository;

    public function __construct(EntriesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function generate(string $period = 'daily'): array
    {
        $hours = $period === 'weekly' ? 168 : 24;
        $since = now()->subHours($hours);

        $counts = $this->repository->getCounts(['since' => $since]);
        $requestStats = $this->repository->getStats(EntryType::REQUEST, ['since' => $since->toDateTimeString()]);

        $exceptions = $this->repository->get(EntryType::EXCEPTION, [
            'limit' => 5,
            'since' => $since,
        ]);

        return [
            'period' => $period,
            'from' => $since->toDateTimeString(),
            'to' => now()->toDateTimeString(),
            'counts' => $counts,
            'request_stats' => [
                'total' => $requestStats['total'] ?? 0,
                'avg_duration' => round($requestStats['avg_duration'] ?? 0, 2),
                'max_duration' => round($requestStats['max_duration'] ?? 0, 2),
            ],
            'top_exceptions' => $exceptions->map(fn ($e) => [
                'class' => $e->content['class'] ?? 'Unknown',
                'message' => \Illuminate\Support\Str::limit($e->content['message'] ?? '', 80),
                'count' => 1,
            ])->values()->toArray(),
        ];
    }
}
