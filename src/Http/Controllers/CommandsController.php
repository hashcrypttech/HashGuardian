<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class CommandsController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::COMMAND, [
            'before' => $request->input('before'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'limit' => 50,
        ]);

        return view('hashguardian::commands.index', [
            'entries' => $entries,
        ]);
    }
}
