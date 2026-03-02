<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class MetricsApiController extends Controller
{
    public function index(Request $request, EntriesRepository $repository): JsonResponse
    {
        $entries = $repository->get(EntryType::METRIC, [
            'since' => $request->input('since', now()->subHours(24)),
            'until' => $request->input('until'),
            'tag' => $request->input('name') ? 'metric:' . $request->input('name') : null,
            'limit' => min((int) $request->input('limit', 50), 100),
        ]);

        return response()->json([
            'data' => $entries->map(fn ($e) => [
                'uuid' => $e->uuid,
                'name' => $e->content['name'] ?? null,
                'value' => $e->content['value'] ?? null,
                'metric_type' => $e->content['metric_type'] ?? null,
                'metadata' => $e->content['metadata'] ?? [],
                'created_at' => $e->created_at,
            ]),
            'count' => $entries->count(),
        ]);
    }
}
