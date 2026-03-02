<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class ActivityApiController extends Controller
{
    public function index(Request $request, EntriesRepository $repository): JsonResponse
    {
        $filters = [
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'tag' => $request->input('user') ? 'user:' . $request->input('user') : $request->input('tag'),
            'limit' => min((int) $request->input('limit', 50), 100),
        ];

        $entries = $repository->get(EntryType::ACTIVITY, array_filter($filters));

        return response()->json([
            'data' => $entries->map(fn ($e) => [
                'uuid' => $e->uuid,
                'content' => $e->content,
                'created_at' => $e->created_at,
            ]),
            'count' => $entries->count(),
        ]);
    }
}
