<?php

namespace Hashcrypttech\HashGuardian\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('hashguardian_track_activity', true);
        return $next($request);
    }
}
