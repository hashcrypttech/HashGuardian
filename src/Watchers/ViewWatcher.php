<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ReflectionFunction;
use Hashcrypttech\HashGuardian\EntryType;
use Hashcrypttech\HashGuardian\IncomingEntry;

class ViewWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(
            $this->option('events', 'composing:*'),
            [$this, 'handleViewEvent']
        );
    }

    public function handleViewEvent(string $event, array $data): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $view = $data[0] ?? null;

        if (! $view instanceof View) {
            return;
        }

        $viewName = $view->getName();

        if ($this->shouldIgnore($viewName)) {
            return;
        }

        $entry = IncomingEntry::make(EntryType::VIEW, array_filter([
            'name' => $viewName,
            'path' => $this->extractPath($view),
            'data' => $this->extractDataKeys($view),
            'composers' => $this->formatComposers($view),
        ]));

        $entry->familyHash(md5($viewName));
        $entry->tags(['view:' . $viewName]);

        $this->record($entry);
    }

    protected function extractPath(View $view): string
    {
        $path = $view->getPath();

        if (Str::startsWith($path, base_path())) {
            $path = substr($path, strlen(base_path()));
        }

        return $path;
    }

    protected function extractDataKeys(View $view): array
    {
        return collect($view->getData())
            ->reject(fn ($value, $key) => in_array($key, ['app', '__env', 'obLevel', 'errors']))
            ->keys()
            ->toArray();
    }

    protected function formatComposers(View $view): array
    {
        $name = $view->getName();

        try {
            return collect([
                'composing: ' . $name,
                'creating: ' . $name,
            ])->map(function ($eventName) {
                $type = Str::startsWith($eventName, 'creating:') ? 'creator' : 'composer';

                return $this->getComposersForEvent($eventName)
                    ->map(fn ($composer) => [
                        'name' => $composer,
                        'type' => $type,
                    ]);
            })->collapse()->values()->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function getComposersForEvent(string $eventName): \Illuminate\Support\Collection
    {
        try {
            return collect(app('events')->getListeners($eventName))
                ->map(function ($listener) {
                    $variables = (new ReflectionFunction($listener))->getStaticVariables();
                    $resolved = $variables['listener'] ?? null;

                    if ($resolved === null) {
                        return null;
                    }

                    if (is_string($resolved)) {
                        return $resolved;
                    }

                    if (is_array($resolved) && is_object($resolved[0])) {
                        $className = get_class($resolved[0]);
                        if (Str::contains($className, 'Hashcrypttech\\HashGuardian')) {
                            return null;
                        }
                        return $className . '@' . $resolved[1];
                    }

                    if (is_array($resolved) && is_string($resolved[0])) {
                        return $resolved[0] . '@' . $resolved[1];
                    }

                    if ($resolved instanceof Closure) {
                        $ref = new ReflectionFunction($resolved);
                        $staticVars = $ref->getStaticVariables();

                        if (isset($staticVars['class'], $staticVars['method'])) {
                            return $staticVars['class'] . '@' . $staticVars['method'];
                        }

                        return 'Closure(' . basename($ref->getFileName()) . ':' . $ref->getStartLine() . ')';
                    }

                    if (is_object($resolved) && is_callable($resolved)) {
                        return get_class($resolved) . '@__invoke';
                    }

                    return null;
                })
                ->filter();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function shouldIgnore(string $viewName): bool
    {
        return Str::is($this->option('ignore', []), $viewName);
    }
}
