<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class EntriesApiController extends Controller
{
    public function index(Request $request, EntriesRepository $repository, string $type): JsonResponse
    {
        $validTypes = EntryType::all();
        if (! in_array($type, $validTypes)) {
            return response()->json(['error' => 'Invalid entry type.'], 400);
        }

        $filters = [
            'before' => $request->input('before'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'tag' => $request->input('tag'),
            'family_hash' => $request->input('family_hash'),
            'limit' => min((int) $request->input('limit', 50), 100),
        ];

        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }

        if ($request->filled('min_duration')) {
            $filters['min_duration'] = $request->input('min_duration');
        }

        $entries = $repository->get($type, array_filter($filters));

        return response()->json([
            'data' => $entries->map(fn ($e) => [
                'uuid' => $e->uuid,
                'batch_id' => $e->batch_id,
                'type' => $e->type,
                'content' => $e->content,
                'duration' => $e->duration,
                'status' => $e->status,
                'family_hash' => $e->family_hash,
                'created_at' => $e->created_at,
            ]),
            'count' => $entries->count(),
            'has_more' => $entries->count() >= ($filters['limit'] ?? 50),
        ]);
    }

    public function show(string $uuid, EntriesRepository $repository): JsonResponse
    {
        $entry = $repository->find($uuid);

        if (! $entry) {
            return response()->json(['error' => 'Entry not found.'], 404);
        }

        return response()->json([
            'data' => [
                'uuid' => $entry->uuid,
                'batch_id' => $entry->batch_id,
                'type' => $entry->type,
                'content' => $entry->content,
                'duration' => $entry->duration,
                'status' => $entry->status,
                'family_hash' => $entry->family_hash,
                'tags' => $entry->tags ?? [],
                'created_at' => $entry->created_at,
            ],
        ]);
    }
}
