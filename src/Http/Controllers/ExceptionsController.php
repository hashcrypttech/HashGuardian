<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class ExceptionsController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::EXCEPTION, [
            'before' => $request->input('before'),
            'tag' => $request->input('tag'),
            'family_hash' => $request->input('family_hash'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'limit' => 50,
        ]);

        return view('hashguardian::exceptions.index', [
            'entries' => $entries,
        ]);
    }

    public function show(string $uuid, EntriesRepository $repository)
    {
        $entry = $repository->find($uuid);

        if (! $entry) {
            abort(404);
        }

        $batchEntries = $repository->getByBatchId($entry->batch_id);

        return view('hashguardian::exceptions.show', [
            'entry' => $entry,
            'batchEntries' => $batchEntries,
        ]);
    }
}
