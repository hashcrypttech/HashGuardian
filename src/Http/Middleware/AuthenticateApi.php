<?php

namespace Hashcrypttech\HashGuardian\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'Unauthorized. Bearer token required.'], 401);
        }

        $connection = config('hashguardian.storage.database.connection', config('database.default'));
        $hashedToken = hash('sha256', $token);

        $tokenRecord = DB::connection($connection)
            ->table('hashguardian_api_tokens')
            ->where('token', $hashedToken)
            ->first();

        if (! $tokenRecord) {
            return response()->json(['error' => 'Invalid API token.'], 401);
        }

        if ($tokenRecord->expires_at && now()->gt($tokenRecord->expires_at)) {
            return response()->json(['error' => 'Token has expired.'], 401);
        }

        if ($ability) {
            $abilities = json_decode($tokenRecord->abilities ?? '[]', true);
            if (! empty($abilities) && ! in_array($ability, $abilities)) {
                return response()->json(['error' => 'Token does not have the required ability: ' . $ability], 403);
            }
        }

        DB::connection($connection)
            ->table('hashguardian_api_tokens')
            ->where('id', $tokenRecord->id)
            ->update(['last_used_at' => now()]);

        $request->attributes->set('hashguardian_token', $tokenRecord);

        // Rate limiting
        $rateLimit = config('hashguardian.api.rate_limit', 60);
        $key = 'hashguardian_api:' . $tokenRecord->id;

        if (RateLimiter::tooManyAttempts($key, $rateLimit)) {
            return response()->json([
                'error' => 'Rate limit exceeded.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
