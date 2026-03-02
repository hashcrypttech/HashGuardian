<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class MetricsController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::METRIC, [
            'since' => $request->input('since', now()->subHours(24)),
            'until' => $request->input('until'),
            'limit' => 100,
        ]);

        $grouped = $entries->groupBy(fn ($e) => $e->content['name'] ?? 'unknown');
        $metrics = [];

        foreach ($grouped as $name => $items) {
            $values = $items->pluck('content')->map(fn ($c) => $c['value'] ?? 0);
            $metrics[] = [
                'name' => $name,
                'count' => $items->count(),
                'latest' => $values->first(),
                'avg' => round($values->avg(), 2),
                'min' => $values->min(),
                'max' => $values->max(),
                'type' => $items->first()->content['metric_type'] ?? 'gauge',
                'entries' => $items->take(20),
            ];
        }

        return view('hashguardian::metrics.index', [
            'metrics' => $metrics,
            'entries' => $entries,
        ]);
    }
}
