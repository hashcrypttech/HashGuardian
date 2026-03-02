<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;

class TimelineController extends Controller
{
    public function show(string $batchId, EntriesRepository $repository)
    {
        $entries = $repository->getByBatchId($batchId);

        if ($entries->isEmpty()) {
            abort(404);
        }

        return view('hashguardian::partials.timeline', [
            'entries' => $entries,
            'batchId' => $batchId,
        ]);
    }
}
