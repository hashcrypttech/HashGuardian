<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class DashboardController extends Controller
{
    public function index(EntriesRepository $repository)
    {
        $counts = $repository->getCounts([
            'since' => now()->subHours(24),
        ]);

        $recentExceptions = $repository->get(EntryType::EXCEPTION, [
            'limit' => 5,
            'since' => now()->subHours(24),
        ]);

        $slowQueries = $repository->get(EntryType::QUERY, [
            'limit' => 5,
            'since' => now()->subHours(24),
        ])->sortByDesc('duration')->take(5);

        $failedJobs = $repository->get(EntryType::JOB, [
            'limit' => 5,
            'status' => 'failed',
            'since' => now()->subHours(24),
        ]);

        $recentRequests = $repository->get(EntryType::REQUEST, [
            'limit' => 10,
            'since' => now()->subHours(24),
        ]);

        $hourlyBreakdown = $this->getHourlyBreakdown($repository);

        return view('hashguardian::dashboard.index', [
            'counts' => $counts,
            'recentExceptions' => $recentExceptions,
            'slowQueries' => $slowQueries,
            'failedJobs' => $failedJobs,
            'recentRequests' => $recentRequests,
            'hourlyBreakdown' => $hourlyBreakdown,
            'entryTypes' => EntryType::labels(),
        ]);
    }

    protected function getHourlyBreakdown(EntriesRepository $repository): array
    {
        $breakdown = [];
        for ($i = 23; $i >= 0; $i--) {
            $start = now()->subHours($i + 1);
            $end = now()->subHours($i);
            $counts = $repository->getCounts([
                'since' => $start,
                'until' => $end,
            ]);
            $breakdown[] = [
                'hour' => $end->format('H:00'),
                'total' => array_sum($counts),
                'requests' => $counts[EntryType::REQUEST] ?? 0,
                'exceptions' => $counts[EntryType::EXCEPTION] ?? 0,
            ];
        }
        return $breakdown;
    }
}
