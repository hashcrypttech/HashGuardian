<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Services\HtopDataCollector;

class HtopController extends Controller
{
    public function index()
    {
        return view('hashguardian::htop.index');
    }

    public function data(): JsonResponse
    {
        $collector = new HtopDataCollector();

        return response()->json($collector->collect());
    }
}
