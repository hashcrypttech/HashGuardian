<?php

namespace Hashcrypttech\HashGuardian\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Hashcrypttech\HashGuardian\HashGuardian;

class Authorize
{
    protected HashGuardian $guardian;

    public function __construct(HashGuardian $guardian)
    {
        $this->guardian = $guardian;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->guardian->check($request)) {
            abort(403);
        }

        return $next($request);
    }
}
