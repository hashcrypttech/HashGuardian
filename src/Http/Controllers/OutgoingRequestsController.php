<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class OutgoingRequestsController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::OUTGOING_REQUEST, [
            'before' => $request->input('before'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'limit' => 50,
        ]);

        return view('hashguardian::outgoing-requests.index', [
            'entries' => $entries,
        ]);
    }
}
