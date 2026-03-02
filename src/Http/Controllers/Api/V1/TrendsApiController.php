<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Hashcrypttech\HashGuardian\Aggregation\MetricsAggregator;

class TrendsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $aggregator = new MetricsAggregator(config('hashguardian.storage.database.connection'));

        $type = $request->input('type', 'request');
        $metric = $request->input('metric', 'count');
        $period = $request->input('period', 'hourly');
        $from = Carbon::parse($request->input('from', now()->subDay()));
        $to = Carbon::parse($request->input('to', now()));

        $data = $aggregator->getAggregates($type, $metric, $period, $from, $to);

        return response()->json([
            'data' => $data->map(fn ($r) => [
                'bucket' => $r->bucket,
                'value' => round((float) $r->value, 4),
                'sample_count' => $r->sample_count,
            ]),
            'meta' => compact('type', 'metric', 'period'),
        ]);
    }
}
