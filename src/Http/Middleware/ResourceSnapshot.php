<?php

namespace Hashcrypttech\HashGuardian\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Hashcrypttech\HashGuardian\ServerMetricsCollector;

class ResourceSnapshot
{
    public function handle(Request $request, Closure $next)
    {
        if (config('hashguardian.server_monitoring.enabled', false)) {
            $request->attributes->set('hashguardian_resource_snapshot', ServerMetricsCollector::snapshot());
        }

        return $next($request);
    }
}
