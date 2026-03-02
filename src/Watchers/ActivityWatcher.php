<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class ActivityWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app->terminating(function () use ($app) {
            $this->recordActivity($app);
        });
    }

    protected function recordActivity(Application $app): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $request = $app['request'];

        if (! $this->option('track_guests', false) && ! $request->user()) {
            return;
        }

        if ($this->shouldIgnore($request)) {
            return;
        }

        $user = $request->user();
        $entry = IncomingEntry::make(EntryType::ACTIVITY, [
            'user' => $user ? [
                'id' => $user->getAuthIdentifier(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ] : null,
            'action' => $request->method(),
            'uri' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'route_action' => $this->getControllerAction($request),
            'route_params' => $request->route()?->parameters() ?? [],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->hasSession() ? substr(sha1($request->session()->getId()), 0, 12) : null,
            'referer' => $request->header('referer'),
        ]);

        $tags = [];
        if ($user) {
            $tags[] = 'user:' . $user->getAuthIdentifier();
        }
        if ($routeName = $request->route()?->getName()) {
            $tags[] = 'route:' . $routeName;
        }
        $tags[] = 'ip:' . $request->ip();
        if ($request->hasSession()) {
            $tags[] = 'session:' . substr(sha1($request->session()->getId()), 0, 12);
        }

        $entry->tags($tags);
        $entry->familyHash(md5($request->method() . $request->path()));

        $this->record($entry);
    }

    protected function shouldIgnore($request): bool
    {
        $ignorePaths = $this->option('ignore_paths', ['/hashguardian*', '/_debugbar*', '/sanctum*']);
        foreach ($ignorePaths as $pattern) {
            if (Str::is($pattern, '/' . $request->path()) || Str::is($pattern, $request->path())) {
                return true;
            }
        }

        $ignoreMethods = $this->option('ignore_methods', ['HEAD', 'OPTIONS']);
        if (in_array($request->method(), $ignoreMethods)) {
            return true;
        }

        return false;
    }

    protected function getControllerAction($request): ?string
    {
        $route = $request->route();
        if (! $route) return null;
        $action = $route->getActionName();
        return $action !== 'Closure' ? $action : null;
    }
}
