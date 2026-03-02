<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class JobsController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::JOB, [
            'before' => $request->input('before'),
            'tag' => $request->input('tag'),
            'status' => $request->input('status'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'limit' => 50,
        ]);

        return view('hashguardian::jobs.index', [
            'entries' => $entries,
        ]);
    }

    public function show(string $uuid, EntriesRepository $repository)
    {
        $entry = $repository->find($uuid);

        if (! $entry) {
            abort(404);
        }

        $executionBatchId = $this->resolveExecutionBatchId($entry, $repository);

        $queries = collect();
        $outgoingRequests = collect();
        $exceptions = collect();
        $totalQueryTime = 0;
        $executionEntry = null;

        if ($executionBatchId) {
            $batchEntries = $repository->getByBatchId($executionBatchId);

            $queries = $batchEntries->where('type', EntryType::QUERY)->values();
            $outgoingRequests = $batchEntries->where('type', EntryType::OUTGOING_REQUEST)->values();
            $exceptions = $batchEntries->where('type', EntryType::EXCEPTION)->values();
            $totalQueryTime = $queries->sum('duration');

            $status = $entry->status ?? ($entry->content['status'] ?? null);
            if ($status === 'queued') {
                $executionEntry = $batchEntries
                    ->where('type', EntryType::JOB)
                    ->whereIn('status', ['processed', 'failed'])
                    ->first();
            }
        }

        return view('hashguardian::jobs.show', [
            'entry' => $entry,
            'executionEntry' => $executionEntry,
            'queries' => $queries,
            'outgoingRequests' => $outgoingRequests,
            'exceptions' => $exceptions,
            'totalQueryTime' => $totalQueryTime,
        ]);
    }

    /**
     * For "processed"/"failed" entries, their batch_id contains the execution data.
     * For "queued" entries, find the corresponding processed/failed entry and use its batch_id.
     */
    protected function resolveExecutionBatchId(object $entry, EntriesRepository $repository): ?string
    {
        $status = $entry->status ?? ($entry->content['status'] ?? null);

        if (in_array($status, ['processed', 'failed'])) {
            return $entry->batch_id;
        }

        $jobName = $entry->content['name'] ?? $entry->content['job'] ?? null;

        if (! $jobName) {
            return null;
        }

        $connection = config('hashguardian.storage.database.connection', config('database.default'));
        $executionEntry = DB::connection($connection)
            ->table('hashguardian_entries')
            ->where('type', EntryType::JOB)
            ->where('family_hash', $entry->family_hash)
            ->whereIn('status', ['processed', 'failed'])
            ->where('created_at', '>=', $entry->created_at)
            ->orderBy('created_at')
            ->first();

        return $executionEntry?->batch_id;
    }
}
