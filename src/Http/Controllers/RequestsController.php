<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\Storage\DatabaseEntriesRepository;

class RequestsController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $filters = [
            'before' => $request->input('before'),
            'tag' => $request->input('tag'),
            'family_hash' => $request->input('family_hash'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'limit' => 50,
        ];

        if ($request->filled('status_code')) {
            $filters['status'] = $request->input('status_code');
        }

        if ($request->filled('status_group')) {
            $filters['status_group'] = $request->input('status_group');
        }

        if ($request->filled('min_duration')) {
            $filters['min_duration'] = $request->input('min_duration');
        }

        $entries = $repository->get(EntryType::REQUEST, $filters);

        $timeOpts = array_filter([
            'since' => $request->input('since'),
            'until' => $request->input('until'),
        ]);

        $stats = $repository->getStats(EntryType::REQUEST, $timeOpts);
        $statusCodes = [];
        if ($repository instanceof DatabaseEntriesRepository) {
            $statusCodes = $repository->getStatusCodeCounts(EntryType::REQUEST, $timeOpts);
        }

        $statusGroups = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0];
        foreach ($statusCodes as $code => $count) {
            $c = (int) $code;
            if ($c >= 200 && $c < 300) $statusGroups['2xx'] += $count;
            elseif ($c >= 300 && $c < 400) $statusGroups['3xx'] += $count;
            elseif ($c >= 400 && $c < 500) $statusGroups['4xx'] += $count;
            elseif ($c >= 500) $statusGroups['5xx'] += $count;
        }

        return view('hashguardian::requests.index', [
            'entries' => $entries,
            'stats' => $stats,
            'statusCodes' => $statusCodes,
            'statusGroups' => $statusGroups,
            'currentStatusGroup' => $request->input('status_group', ''),
            'currentMinDuration' => $request->input('min_duration', ''),
        ]);
    }

    public function chartData(Request $request, EntriesRepository $repository)
    {
        $period = $request->input('period', '3h');

        $hours = match ($period) {
            '1h' => 1,
            '3h' => 3,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 3,
        };

        $since = $request->input('since', now()->subHours($hours)->toDateTimeString());
        $until = $request->input('until', now()->toDateTimeString());

        $data = [];
        if ($repository instanceof DatabaseEntriesRepository) {
            $data = $repository->getRequestsTimeline(EntryType::REQUEST, [
                'since' => $since,
                'until' => $until,
            ]);
        }

        return response()->json($data);
    }

    public function show(string $uuid, EntriesRepository $repository)
    {
        $entry = $repository->find($uuid);

        if (! $entry) {
            abort(404);
        }

        $batchEntries = $repository->getByBatchId($entry->batch_id);

        return view('hashguardian::requests.show', [
            'entry' => $entry,
            'batchEntries' => $batchEntries,
        ]);
    }
}
