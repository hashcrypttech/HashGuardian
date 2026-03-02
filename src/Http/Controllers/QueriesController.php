<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class QueriesController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::QUERY, [
            'before' => $request->input('before'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'family_hash' => $request->input('family_hash'),
            'limit' => 50,
        ]);

        return view('hashguardian::queries.index', [
            'entries' => $entries,
        ]);
    }
}
